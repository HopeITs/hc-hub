<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Browsershot\Browsershot;

class ContractExportController extends Controller
{
    public function pdf(Contract $contract)
    {
        Gate::authorize('export', $contract);

        $cityId = auth()->user()->activeCityId();
        if ($cityId && $contract->employee?->city_id !== $cityId) {
            abort(403);
        }

        return $this->render($contract);
    }

    // Local-only: renders the export for any contract without the auth/city
    // gates above, as plain HTML (not through Browsershot), so the print
    // layout can be iterated on quickly in the browser's own devtools.
    public function preview(Contract $contract)
    {
        abort_unless(app()->isLocal(), 404);

        return $this->view($contract);
    }

    private function view(Contract $contract, bool $inlineAssets = false)
    {
        $contract->loadMissing([
            'employee', 'employee.city',
            'position.center', 'position.department', 'position.jobLevel', 'position.program',
            'contractType',
        ]);

        $category = $contract->contractType?->category ?? 'annual';
        $pagesMap = ['annual' => 5, 'trial' => 4, 'service' => 4];
        $totalPages = $pagesMap[$category] ?? 1;
        $locale = app()->getLocale();
        $dir = $locale === 'ar' ? 'rtl' : 'ltr';

        return view('pdf.contract', [
            'contract' => $contract,
            'category' => $category,
            'totalPages' => $totalPages,
            'locale' => $locale,
            'dir' => $dir,
            'fileTitle' => $this->fileName($contract, $category),
            'inlineAssets' => $inlineAssets,
        ]);
    }

    private function fileName(Contract $contract, string $category): string
    {
        return sprintf(
            '%s-%s-%s.pdf',
            Str::slug($contract->employee?->name ?? 'contract'),
            $category,
            now()->format('Ymd'),
        );
    }

    private function render(Contract $contract)
    {
        // Fonts and images are inlined as data URIs for this path only: the
        // headless browser renders from a file:// temp dir and can't fetch
        // asset() URLs back through the app's own web server.
        $html = $this->view($contract, inlineAssets: true)->render();

        // Each attempt gets its own throwaway profile directory. A Chrome that
        // was killed mid-render leaves a SingletonLock behind, and every later
        // launch pointed at that same profile dies during startup — so retrying
        // into a shared profile just reproduces the original failure.
        $pdf = retry(3, function () use ($html) {
            $profile = $this->makeProfileDir();

            try {
                $pdf = $this->browsershot($html, $profile)->pdf();
            } finally {
                File::deleteDirectory($profile);
            }

            // Browsershot parses node's entire stdout as JSON and json_decode()
            // failing is not an error to it: pdf() then base64-decodes null and
            // returns an empty string. One stray line on stdout is enough. Sent
            // as a 200 that becomes a file the browser can't open, so treat
            // anything that isn't a PDF as a failed attempt worth retrying.
            if (! str_starts_with($pdf, '%PDF-')) {
                throw new RuntimeException(sprintf(
                    'Browsershot returned %d bytes that are not a PDF: %s',
                    strlen($pdf),
                    Str::limit(addcslashes(substr($pdf, 0, 200), "\0..\37"), 200),
                ));
            }

            return $pdf;
        }, 500);

        $category = $contract->contractType?->category ?? 'annual';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$this->fileName($contract, $category).'"',
        ]);
    }

    private function browsershot(string $html, string $profile): Browsershot
    {
        $timeout = (int) config('browsershot.timeout');

        $browsershot = Browsershot::html($html)
            ->setChromePath(config('browsershot.chrome_path'))
            ->timeout($timeout)
            // Without this, Browsershot asks puppeteer for the legacy headless
            // shell, which modern Chrome builds no longer contain — the launch
            // then dies before the first page can be created.
            ->newHeadless()
            // browser.cjs otherwise caps every CDP call at 30s regardless of
            // the timeout above, which a large contract can exceed.
            ->protocolTimeout($timeout)
            ->noSandbox()
            ->addChromiumArguments($this->chromiumArguments($profile))
            ->showBackground()
            ->setOption('preferCSSPageSize', true)
            ->waitUntilNetworkIdle();

        if ($nodeBinary = config('browsershot.node_binary')) {
            $browsershot->setNodeBinary($nodeBinary);
        }

        $browsershot->setCustomTempPath($profile.'/tmp');

        return $browsershot;
    }

    /**
     * A private, writable Chrome profile/cache directory for a single render.
     */
    private function makeProfileDir(): string
    {
        $base = config('browsershot.user_data_dir') ?: sys_get_temp_dir().'/hc-hr-chrome';
        $dir = rtrim(strtr($base, DIRECTORY_SEPARATOR, '/'), '/').'/'.Str::random(16);

        File::ensureDirectoryExists($dir.'/tmp', 0755, true);

        return $dir;
    }

    /**
     * @return array<int, string>
     */
    private function chromiumArguments(string $profile): array
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
}
