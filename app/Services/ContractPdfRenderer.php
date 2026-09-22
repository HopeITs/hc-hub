<?php

namespace App\Services;

use App\Models\Contract;
use App\Support\InspectableBrowsershot;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Browsershot\Browsershot;
use Throwable;

/**
 * Renders a contract to PDF through a headless Chrome driven by Browsershot.
 *
 * Everything the render needs to be told about the host — where Chrome and node
 * live, which directories the web user may write to — comes from
 * config/browsershot.php, so a misbehaving server can be corrected from .env
 * alone. `contracts:export-doctor` drives this same class to report what the
 * host actually does with it.
 */
class ContractPdfRenderer
{
    /** Pages in the printed layout, per contract category. */
    private const PAGES_PER_CATEGORY = ['annual' => 5, 'trial' => 4, 'service' => 4];

    /**
     * The printable contract as a Blade view.
     *
     * $inlineAssets embeds fonts and images as data URIs: the headless browser
     * renders from a file:// temp dir and can't fetch asset() URLs back through
     * the app's own web server. On-screen pages keep using normal URLs.
     */
    public function view(Contract $contract, bool $inlineAssets = false): View
    {
        $contract->loadMissing([
            'employee', 'employee.city',
            'position.center', 'position.department', 'position.jobLevel', 'position.program',
            'contractType',
        ]);

        $category = $this->category($contract);
        $locale = app()->getLocale();

        return view('pdf.contract', [
            'contract' => $contract,
            'category' => $category,
            'totalPages' => self::PAGES_PER_CATEGORY[$category] ?? 1,
            'locale' => $locale,
            'dir' => $locale === 'ar' ? 'rtl' : 'ltr',
            'fileTitle' => $this->fileName($contract),
            'inlineAssets' => $inlineAssets,
        ]);
    }

    public function category(Contract $contract): string
    {
        return $contract->contractType?->category ?? 'annual';
    }

    public function fileName(Contract $contract): string
    {
        return sprintf(
            '%s-%s-%s.pdf',
            Str::slug($contract->employee?->name ?? 'contract'),
            $this->category($contract),
            now()->format('Ymd'),
        );
    }

    /**
     * The contract as raw PDF bytes.
     *
     * @throws RuntimeException when every attempt failed; the message carries
     *                          whatever node, Chrome and the page reported.
     */
    public function render(Contract $contract): string
    {
        return $this->renderHtml($this->view($contract, inlineAssets: true)->render());
    }

    /**
     * @throws RuntimeException
     */
    public function renderHtml(string $html): string
    {
        $attempt = 0;

        // Each attempt gets its own throwaway profile directory. A Chrome that
        // was killed mid-render leaves a SingletonLock behind, and every later
        // launch pointed at that same profile dies during startup — so retrying
        // into a shared profile just reproduces the original failure.
        return retry(3, function () use ($html, &$attempt) {
            $attempt++;
            $profile = $this->makeProfileDir();
            $target = $profile.'/contract.pdf';

            try {
                $browsershot = $this->browsershot($html, $profile);

                // Chrome writes the PDF to disk itself rather than base64-ing it
                // back through node's stdout. pdf() parses that stdout as JSON and
                // treats a parse failure as a success: it base64-decodes null and
                // returns an empty string, which reaches the browser as a 200 with
                // a 0-byte body. One stray line on stdout, or a pipe the host
                // truncated, is enough. A file on disk has nothing to corrupt.
                try {
                    $browsershot->savePdf($target);
                } catch (Throwable $e) {
                    throw $this->failure($browsershot, $attempt, cause: $e);
                }

                $pdf = is_file($target) ? (string) file_get_contents($target) : '';

                if (! str_starts_with($pdf, '%PDF-')) {
                    throw $this->failure($browsershot, $attempt, bytes: $pdf);
                }

                return $pdf;
            } finally {
                File::deleteDirectory($profile);
            }
        }, 500);
    }

    public function browsershot(string $html, string $profile): InspectableBrowsershot
    {
        $timeout = (int) config('browsershot.timeout');

        $browsershot = InspectableBrowsershot::html($html)
            ->setChromePath(config('browsershot.chrome_path'))
            ->timeout($timeout)
            // browser.cjs otherwise caps every CDP call at 30s regardless of
            // the timeout above, which a large contract can exceed.
            ->protocolTimeout($timeout)
            ->noSandbox()
            ->addChromiumArguments($this->chromiumArguments($profile))
            ->showBackground()
            ->setOption('preferCSSPageSize', true)
            // Keeps the options JSON off the command line, where a host's own
            // argument limits and shell escaping can mangle it.
            ->writeOptionsToFile()
            // node otherwise echoes the URL of every request the page made back
            // on stdout — and since the fonts and images are inlined as data
            // URIs, that is the whole document again, ~350 KB of it. Nothing
            // reads the list, and a smaller stdout is a stdout that survives.
            ->disableCaptureURLS()
            ->waitUntilNetworkIdle()
            // Chrome still writes to $HOME (crashpad, the NSS db) even when it
            // is given a --user-data-dir, and on cPanel-style hosts the web
            // user's home is not writable: the browser then dies during
            // startup, surfacing as "Target.setAutoAttach: Target closed"
            // from the web server while the same command works over SSH.
            // TMPDIR deliberately points somewhere else — see makeSocketDir().
            ->setNodeEnv(array_filter([
                'HOME' => $profile,
                'XDG_CONFIG_HOME' => $profile.'/config',
                'XDG_CACHE_HOME' => $profile.'/cache',
                'XDG_DATA_HOME' => $profile.'/data',
                'TMPDIR' => $this->makeSocketDir(),
                // Chrome resolves most of its libraries only once it opens a
                // page, so a host missing them still passes `chrome --version`
                // and then dies with "Target.setAutoAttach: Target closed".
                'LD_LIBRARY_PATH' => $this->libraryPath(),
            ]));

        // `chrome-headless-shell` is headless by construction and rejects the
        // switch; the full Chrome binary needs it, because the legacy headless
        // mode it would otherwise ask for was removed from Chrome in M132 and
        // the launch dies before the first page can be created.
        if (! $this->usesHeadlessShell()) {
            $browsershot->newHeadless();
        }

        if ($nodeBinary = config('browsershot.node_binary')) {
            $browsershot->setNodeBinary($nodeBinary);
        }

        // Without this Browsershot resolves puppeteer through `npm root -g`,
        // which on a host with no global npm silently yields nothing.
        if ($nodeModulePath = config('browsershot.node_module_path')) {
            $browsershot->setNodeModulePath($nodeModulePath);
        }

        $browsershot->setCustomTempPath($profile.'/tmp');

        return $browsershot;
    }

    /**
     * A private, writable Chrome profile/cache directory for a single render.
     */
    public function makeProfileDir(): string
    {
        $dir = $this->profileBaseDir().'/'.Str::random(16);

        foreach (['tmp', 'cache', 'config', 'data'] as $sub) {
            File::ensureDirectoryExists($dir.'/'.$sub, 0755, true);
        }

        return $dir;
    }

    /**
     * Whether the configured binary is Chrome's headless shell.
     *
     * The shell is the build meant for servers: no desktop integration, so it
     * does not reach for D-Bus, a keyring or the PCI bus, none of which a
     * locked-down shared host lets it have. Full Chrome exits during startup
     * on such a host even in headless mode.
     */
    public function usesHeadlessShell(): bool
    {
        return str_contains(basename((string) config('browsershot.chrome_path')), 'headless-shell');
    }

    /**
     * LD_LIBRARY_PATH for the render, or null to leave the host's own alone.
     *
     * Configured directories go first so a user-owned copy of a library wins
     * over a missing system one, with whatever the host already exports kept
     * behind it.
     */
    public function libraryPath(): ?string
    {
        $configured = trim((string) config('browsershot.ld_library_path'), ': ');

        if ($configured === '') {
            return null;
        }

        $inherited = trim((string) getenv('LD_LIBRARY_PATH'), ': ');

        return $inherited === '' ? $configured : $configured.':'.$inherited;
    }

    public function profileBaseDir(): string
    {
        $base = config('browsershot.user_data_dir') ?: sys_get_temp_dir().'/hc-hr-chrome';

        return rtrim(strtr($base, DIRECTORY_SEPARATOR, '/'), '/');
    }

    /**
     * A short, writable TMPDIR for Chrome's process-singleton socket.
     *
     * The socket is a unix socket, so its whole path must fit in sun_path
     * (~108 bytes). Chrome appends about 45 characters to TMPDIR for it, and
     * the per-render profile directory is normally far too deep — pointing
     * TMPDIR at the profile aborts the launch with "Socket path too long".
     */
    public function makeSocketDir(): string
    {
        $dir = rtrim(strtr(
            config('browsershot.tmp_dir') ?: sys_get_temp_dir(),
            DIRECTORY_SEPARATOR,
            '/',
        ), '/');

        File::ensureDirectoryExists($dir, 0755, true);

        return $dir;
    }

    /**
     * @return array<int, string>
     */
    public function chromiumArguments(string $profile): array
    {
        $args = [
            'disable-dev-shm-usage',
            'disable-gpu',
            'disable-software-rasterizer',
            'disable-features=site-per-process,IsolateOrigins',
            'disable-background-networking',
            'disable-background-timer-throttling',
            'disable-backgrounding-occluded-windows',
            'disable-renderer-backgrounding',
            'disable-default-apps',
            'disable-extensions',
            'disable-sync',
            'metrics-recording-only',
            'mute-audio',
            'no-first-run',
            'user-data-dir='.$profile,
            'crash-dumps-dir='.$profile,
            'disk-cache-dir='.$profile.'/cache',
        ];

        // --no-zygote only makes sense alongside --single-process: on its own
        // it leaves Chrome unable to spawn a renderer, which surfaces as
        // "Target.createTarget: Target closed" the moment a page is opened.
        if (config('browsershot.single_process')) {
            $args[] = 'single-process';
            $args[] = 'no-zygote';
        }

        return array_merge($args, config('browsershot.extra_args', []));
    }

    /**
     * Turn a failed attempt into an exception that says what actually happened.
     *
     * Browsershot collects node's exception, the page's own errors, its console
     * and its failed requests, then drops all of them unless asked — without
     * which a broken render on the server reports only its own byte count,
     * which is never enough to act on.
     */
    private function failure(
        Browsershot $browsershot,
        int $attempt,
        ?Throwable $cause = null,
        ?string $bytes = null,
    ): RuntimeException {
        $parts = [];

        if ($cause) {
            $parts[] = 'Browsershot threw '.class_basename($cause).': '.Str::limit($cause->getMessage(), 1000);
        } elseif ($bytes === '') {
            $parts[] = 'Chrome exited without writing a PDF file.';
        } else {
            $parts[] = sprintf(
                'Chrome wrote %d bytes that are not a PDF: %s',
                strlen((string) $bytes),
                Str::limit(addcslashes(substr((string) $bytes, 0, 200), "\0..\37"), 200),
            );
        }

        $output = $browsershot->getOutput();

        if ($exception = $output?->getException()) {
            $parts[] = 'node: '.Str::limit($exception, 800);
        }

        $parts = array_merge(
            $parts,
            $this->summarise($output?->getPageErrors(), 'page error', ['name', 'message']),
            $this->summarise($output?->getConsoleMessages(), 'console', ['type', 'message']),
            $this->summarise($output?->getFailedRequests(), 'failed request', ['status', 'url']),
        );

        $message = implode(' | ', $parts);

        // The 500 page shows only the last attempt, and in production it shows
        // nothing at all — so every attempt goes to the log as well.
        Log::error('Contract PDF render attempt '.$attempt.' failed', ['detail' => $message]);

        return new RuntimeException($message, 0, $cause);
    }

    /**
     * @param  array<int, mixed>|null  $entries
     * @param  array<int, string>  $keys
     * @return array<int, string>
     */
    private function summarise(?array $entries, string $label, array $keys, int $limit = 5): array
    {
        $lines = [];

        foreach (array_slice($entries ?? [], 0, $limit) as $entry) {
            $entry = (array) $entry;
            $values = array_filter(array_map(
                fn (string $key) => isset($entry[$key]) ? (string) $entry[$key] : null,
                $keys,
            ));

            if ($values !== []) {
                $lines[] = $label.': '.Str::limit(implode(' ', $values), 300);
            }
        }

        return $lines;
    }
}
