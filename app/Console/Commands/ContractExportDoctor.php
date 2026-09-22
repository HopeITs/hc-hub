<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Services\ContractPdfRenderer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Reports what the host actually does when asked to render a contract PDF.
 *
 * Browsershot hides raw stdout, stderr and the exit code whenever node exits 0,
 * so a render that "works" while producing nothing leaves no evidence behind.
 * This command runs the very same node command itself, with all three captured,
 * after checking each thing the render depends on.
 */
class ContractExportDoctor extends Command
{
    protected $signature = 'contracts:export-doctor
                            {contract? : Render this contract instead of a trivial page}
                            {--keep : Leave the temp profile directory in place}';

    protected $description = 'Diagnose contract PDF export on this host (node, Chrome, permissions, raw render output)';

    private bool $failed = false;

    /** Extra Chrome flags the standalone launch needed, if any. */
    private array $workingFlags = [];

    public function handle(ContractPdfRenderer $renderer): int
    {
        $this->section('PHP');
        $this->line('  version        '.PHP_VERSION.'  (sapi: '.PHP_SAPI.')');
        $this->line('  memory_limit   '.ini_get('memory_limit'));
        $this->line('  max_exec_time  '.ini_get('max_execution_time'));
        $this->check('proc_open available', function_exists('proc_open'),
            'Symfony Process cannot spawn node without it — remove it from disable_functions.');
        if ($disabled = trim((string) ini_get('disable_functions'))) {
            $this->line('  disabled fns   '.Str::limit($disabled, 200));
        }
        $this->warnIf(
            (bool) config('app.debug') && app()->environment('production'),
            'APP_DEBUG is on in production: the export failure page is exposing your stack traces. Turn it off once this is fixed.',
        );

        $this->section('Configuration (config/browsershot.php)');
        foreach (['chrome_path', 'node_binary', 'node_module_path', 'timeout', 'single_process', 'user_data_dir', 'tmp_dir'] as $key) {
            $value = config("browsershot.$key");
            $this->line(sprintf('  %-16s %s', $key, $value === null || $value === '' ? '(unset)' : var_export($value, true)));
        }
        $this->line('  extra_args       '.(json_encode(config('browsershot.extra_args')) ?: '[]'));

        $this->section('Chrome');
        $chrome = (string) config('browsershot.chrome_path');
        if ($this->check('chrome_path exists', $chrome !== '' && is_file($chrome), "Not a file: {$chrome}. Set BROWSERSHOT_CHROME_PATH to the Chrome/Chromium binary on this host.")) {
            $this->check('chrome_path executable', is_executable($chrome), 'chmod +x '.$chrome.', or the web user cannot run it.');
            $this->probe([$chrome, '--version'], 'chrome --version', timeout: 15);
            $this->sharedLibraries($chrome);
            $this->launchesAlone($chrome);
        }

        if (PHP_OS_FAMILY !== 'Windows') {
            $this->section('Host limits');
            $this->probe(['sh', '-c', 'ulimit -u'], 'max user processes (ulimit -u)');
            // Asked in PHP rather than through df, which CageFS-style hosts
            // refuse to run at all.
            $shm = is_dir('/dev/shm')
                ? (is_writable('/dev/shm')
                    ? 'writable, '.number_format((float) @disk_free_space('/dev/shm') / 1048576, 0).' MB free'
                    : 'present but not writable')
                : 'absent';
            $this->line('  <fg=green>OK  </> /dev/shm '.$shm);
            $this->probe(['sh', '-c', "grep -E '^Mem(Total|Available)' /proc/meminfo | tr -s ' ' | tr '\\n' ' '"], 'memory');
        }

        $this->section('Node');
        $node = (string) (config('browsershot.node_binary') ?: 'node');
        $this->probe([$node, '--version'], 'node --version');
        $browserCjs = base_path('vendor/spatie/browsershot/bin/browser.cjs');
        $this->check('browser.cjs present', is_file($browserCjs), "Missing {$browserCjs} — run composer install on this host.");

        // browser.cjs does require('puppeteer'), resolved from its own directory
        // upwards plus NODE_PATH. Asking node the same question from the same
        // place is the only answer that means anything.
        $resolve = $this->probe(
            [$node, '-e', "console.log(require.resolve('puppeteer'))"],
            "node require.resolve('puppeteer')",
            cwd: dirname($browserCjs),
            env: array_filter(['NODE_PATH' => config('browsershot.node_module_path')]),
        );
        $this->check('puppeteer resolvable from browser.cjs', $resolve['ok'],
            'browser.cjs cannot require puppeteer. Run `npm install --omit=dev` on the server, or set '
            .'BROWSERSHOT_NODE_MODULE_PATH to the node_modules directory that has it.');

        $this->section('Writable directories');
        $profileBase = $renderer->profileBaseDir();
        $this->writable('profile base (BROWSERSHOT_USER_DATA_DIR)', $profileBase);

        try {
            $socketDir = $renderer->makeSocketDir();
            $this->writable('socket dir (BROWSERSHOT_TMP_DIR)', $socketDir);
            // Chrome's process-singleton socket is a unix socket: TMPDIR plus the
            // ~45 characters Chrome appends must stay inside sun_path (~108).
            $this->check(
                'socket dir short enough ('.strlen($socketDir).' chars)',
                strlen($socketDir) <= 60,
                'Chrome will abort with "Socket path too long". Point BROWSERSHOT_TMP_DIR at something short, e.g. /tmp/hc.',
            );
        } catch (Throwable $e) {
            $this->check('socket dir creatable', false, $e->getMessage());
        }

        $this->section('Render');

        return $this->attemptRender($renderer) ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Run the exact node command the export would run, keeping every byte of it.
     */
    private function attemptRender(ContractPdfRenderer $renderer): bool
    {
        $contract = null;

        if ($id = $this->argument('contract')) {
            $contract = Contract::find($id);

            if (! $contract) {
                $this->check('contract '.$id.' exists', false, 'No such contract; falling back to a trivial page.');
            }
        }

        $html = $contract
            ? $renderer->view($contract, inlineAssets: true)->render()
            : '<html><body><h1>export doctor</h1></body></html>';

        $this->line('  source         '.($contract ? 'contract #'.$contract->getKey() : 'trivial page')
            .' ('.number_format(strlen($html) / 1024, 1).' KB of HTML)');

        if ($this->renderOnce($renderer, $html)) {
            return ! $this->failed;
        }

        // If the standalone launch above needed extra flags to come up, the
        // render deserves the same ones before anyone concludes it is broken —
        // and if that is all it took, the answer is one line of .env.
        if ($this->workingFlags !== []) {
            $flags = array_values(array_unique(array_map(
                fn (string $flag) => ltrim($flag, '-'),
                $this->workingFlags,
            )));

            $this->newLine();
            $this->line('<fg=cyan;options=bold>Render, retried with the flags Chrome needed above</>');
            config(['browsershot.extra_args' => array_values(array_unique(
                array_merge(config('browsershot.extra_args', []), $flags),
            ))]);

            if ($this->renderOnce($renderer, $html)) {
                $this->line('       <fg=green>→ Put this in .env and the export works:</>');
                $this->line('       <fg=green>BROWSERSHOT_CHROMIUM_ARGS='.implode(',', $flags).'</>');
            }

            return false;
        }

        // Chrome came up on its own but not under node. A host capping the
        // processes an account may spawn is the usual difference: the zygote and
        // its helpers never appear and puppeteer only sees the target close.
        if (! config('browsershot.single_process')) {
            $this->newLine();
            $this->line('<fg=cyan;options=bold>Render, retried with --single-process</>');
            config(['browsershot.single_process' => true]);

            if ($this->renderOnce($renderer, $html)) {
                $this->line('       <fg=green>→ Set BROWSERSHOT_SINGLE_PROCESS=true in .env. '
                    .'This host caps the processes Chrome may spawn.</>');
            }
        }

        return false;
    }

    /**
     * Run the exact node command the export would run, keeping every byte of it.
     */
    private function renderOnce(ContractPdfRenderer $renderer, string $html): bool
    {
        $profile = $renderer->makeProfileDir();
        $target = $profile.'/contract.pdf';

        try {
            $browsershot = $renderer->browsershot($html, $profile);
            $command = $browsershot->nodeCommand($target);

            $process = is_array($command)
                ? new Process($command, null, $browsershot->nodeEnvironment())
                : Process::fromShellCommandline($command);

            $process->setTimeout((int) config('browsershot.timeout') + 30);

            $started = microtime(true);
            $process->run();
            $elapsed = round(microtime(true) - $started, 1);

            $stdout = $process->getOutput();
            $stderr = $process->getErrorOutput();
            $pdf = is_file($target) ? (string) file_get_contents($target) : '';

            $this->line('  exit code      '.var_export($process->getExitCode(), true).'  ('.$elapsed.'s)');
            $this->line('  stdout         '.strlen($stdout).' bytes'.($stdout === '' ? '' : ': '.$this->snippet($stdout)));
            $this->line('  stderr         '.strlen($stderr).' bytes'.($stderr === '' ? '' : ': '.$this->snippet($stderr)));
            $this->line('  pdf file       '.strlen($pdf).' bytes');

            // Non-JSON on stdout is exactly what makes pdf() hand back an empty
            // string while reporting success — worth naming even though the
            // export no longer routes the document through it.
            if ($stdout !== '' && json_decode(rtrim($stdout), true) === null) {
                $this->warnIf(true, 'stdout is not valid JSON. Something on this host writes to node\'s stdout; '
                    .'that is what broke the old base64 transport.');
            }

            $ok = str_starts_with($pdf, '%PDF-');
            $this->check('PDF produced', $ok, 'Chrome did not write a usable PDF. Its full output follows.');

            // The snippets above keep the summary readable; when it actually
            // failed, the line that explains why is usually past the cut.
            if (! $ok && $stderr !== '') {
                foreach (explode("\n", trim($stderr)) as $line) {
                    $this->line('       <fg=gray>'.addcslashes($line, "\0..\10\13..\37").'</>');
                }
            }

            if ($ok && $this->option('keep')) {
                $kept = storage_path('app/export-doctor.pdf');
                File::ensureDirectoryExists(dirname($kept));
                File::copy($target, $kept);
                $this->line('  saved          '.$kept);
            }

            return $ok;
        } catch (Throwable $e) {
            $this->check('render completed', false, class_basename($e).': '.$e->getMessage());

            return false;
        } finally {
            if (! $this->option('keep')) {
                File::deleteDirectory($profile);
            } else {
                $this->line('  profile kept   '.$profile);
            }
        }
    }

    /**
     * Every library Chrome will need, and whether this host has it.
     *
     * Chrome loads most of them lazily: `--version` prints its string and exits
     * long before the GPU, X11 and NSS stacks are touched, so it happily
     * succeeds on a host that cannot actually open a browser. ldd asks the
     * question that `--version` does not.
     */
    private function sharedLibraries(string $chrome): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return;
        }

        $ldd = $this->capture(['sh', '-c', 'ldd '.escapeshellarg($chrome).' 2>&1']);

        if (! str_contains($ldd['out'], '=>')) {
            $this->warnIf(true, 'ldd unavailable here, skipping the shared-library check.');

            return;
        }

        $missing = [];

        foreach (explode("\n", $ldd['out']) as $line) {
            if (str_contains($line, 'not found')) {
                $missing[] = strtok(trim($line), ' ');
            }
        }

        $missing = array_values(array_unique(array_filter($missing)));

        $this->check(
            'shared libraries resolvable'.($missing === [] ? '' : ' ('.count($missing).' missing)'),
            $missing === [],
            'Chrome cannot load: '.implode(' ', $missing).'. Install them, or put a copy somewhere you own '
            .'and set BROWSERSHOT_LD_LIBRARY_PATH to that directory.',
        );

        foreach ($missing as $lib) {
            $this->line('       <fg=yellow>· '.$lib.'</>');
        }
    }

    /**
     * Start Chrome with nothing between it and us.
     *
     * "Target closed" is only puppeteer reporting that the process went away;
     * Chrome itself normally says why, on its own stderr, and that message
     * never reaches Browsershot.
     */
    private function launchesAlone(string $chrome): void
    {
        // Tried in order, each adding to the one before, stopping at the first
        // that opens a page. A host that refuses the plain launch usually
        // objects to something specific — the keyring Chrome reaches for over
        // D-Bus, the crash handler it forks, the zygote it clones — and naming
        // which of them it was turns a dead end into a BROWSERSHOT_CHROMIUM_ARGS
        // line.
        $ladder = [
            'as configured' => [],
            'without the keyring and crash handler' => [
                '--password-store=basic',
                '--use-mock-keychain',
                '--disable-breakpad',
                '--disable-crash-reporter',
                '--disable-component-update',
                '--disable-client-side-phishing-detection',
            ],
            'also without out-of-process services' => [
                '--disable-features=AudioServiceOutOfProcess,DialMediaRouteProvider,MediaRouter,NetworkService',
                '--disable-audio-output',
            ],
            'also as a single process' => ['--single-process', '--no-zygote'],
        ];

        $extra = [];
        $lastOutput = '';

        foreach ($ladder as $label => $flags) {
            $extra = array_merge($extra, $flags);
            $result = $this->launch($chrome, $extra);
            $lastOutput = $result['out'];

            if ($result['ok']) {
                $this->check('chrome opens a page on its own ('.$label.')', true);

                if ($extra !== []) {
                    $this->failed = true;
                    $this->workingFlags = $extra;
                    $this->line('       <fg=yellow>→ The plain launch fails here. Put these in BROWSERSHOT_CHROMIUM_ARGS '
                        .'(comma separated, no leading --):</>');
                    $this->line('       <fg=green>'.implode(',', array_map(
                        fn (string $flag) => ltrim($flag, '-'),
                        $extra,
                    )).'</>');
                }

                return;
            }

            $this->line(sprintf('  <fg=red>FAIL</> chrome opens a page on its own (%s)', $label));
        }

        $this->failed = true;
        $this->line('       <fg=yellow>→ Chrome will not start on this host under any of the flag sets tried. '
            .'Its own output from the last attempt follows in full.</>');

        // Printed whole, not trimmed: the line that explains an abort is
        // routinely the last one, and a snippet always cuts it off.
        foreach (explode("\n", trim($lastOutput)) as $line) {
            $this->line('       <fg=gray>'.addcslashes($line, "\0..\10\13..\37").'</>');
        }
    }

    /**
     * @param  array<int, string>  $extraFlags
     * @return array{ok: bool, out: string}
     */
    private function launch(string $chrome, array $extraFlags): array
    {
        $profile = rtrim(strtr(sys_get_temp_dir(), DIRECTORY_SEPARATOR, '/'), '/')
            .'/hc-hr-probe-'.getmypid().'-'.count($extraFlags);

        File::ensureDirectoryExists($profile, 0755, true);

        try {
            $result = $this->capture(array_merge([
                $chrome,
                '--headless=new',
                '--no-sandbox',
                '--disable-gpu',
                '--disable-dev-shm-usage',
                '--no-first-run',
                '--user-data-dir='.$profile,
                '--crash-dumps-dir='.$profile,
                '--enable-logging=stderr',
                '--virtual-time-budget=2000',
            ], $extraFlags, ['--dump-dom', 'about:blank']), env: array_filter([
                // The same environment the render gives it — Chrome writes to
                // $HOME even when handed a --user-data-dir.
                'HOME' => $profile,
                'XDG_CONFIG_HOME' => $profile.'/config',
                'XDG_CACHE_HOME' => $profile.'/cache',
                'XDG_DATA_HOME' => $profile.'/data',
                'LD_LIBRARY_PATH' => config('browsershot.ld_library_path'),
            ]), timeout: 60);

            return [
                'ok' => str_contains($result['out'], '<body'),
                'out' => $result['out'],
            ];
        } finally {
            File::deleteDirectory($profile);
        }
    }

    /**
     * @param  array<int, string>  $command
     * @param  array<string, string>  $env
     * @return array{ok: bool, out: string}
     */
    private function probe(array $command, string $label, ?string $cwd = null, array $env = [], int $timeout = 30): array
    {
        $result = $this->capture($command, $cwd, $env, $timeout);

        $this->line(sprintf(
            '  %s %s %s',
            $result['ok'] ? '<fg=green>OK  </>' : '<fg=red>FAIL</>',
            $label,
            $this->snippet($result['out']),
        ));

        return $result;
    }

    /**
     * @param  array<int, string>  $command
     * @param  array<string, string>  $env
     * @return array{ok: bool, out: string}
     */
    private function capture(array $command, ?string $cwd = null, array $env = [], int $timeout = 30): array
    {
        try {
            $process = new Process($command, $cwd, $env === [] ? null : $env);
        } catch (Throwable $e) {
            return ['ok' => false, 'out' => $e->getMessage()];
        }

        $process->setTimeout($timeout);
        $thrown = null;

        try {
            $process->run();
        } catch (Throwable $e) {
            // A process killed by a signal — Chrome aborting is signal 6 — makes
            // run() throw, and everything it printed before dying is still on
            // the process object. Reading it anyway is the whole point of this
            // command; letting the exception replace it was how the first run
            // reported "signaled with signal 6" and nothing else.
            $thrown = $e;
        }

        $out = trim($process->getOutput()."\n".$process->getErrorOutput());

        if ($thrown) {
            $out = trim($thrown->getMessage()."\n".$out);
        }

        return [
            'ok' => $thrown === null && $process->isSuccessful(),
            'out' => $out,
        ];
    }

    private function writable(string $label, string $dir): void
    {
        File::ensureDirectoryExists($dir, 0755, true);

        $this->check($label.' writable ('.$dir.')', is_dir($dir) && is_writable($dir),
            "The web user cannot write to {$dir}. Chrome dies at startup without a writable profile.");
    }

    private function check(string $label, bool $ok, string $remedy = ''): bool
    {
        $this->line(sprintf('  %s %s', $ok ? '<fg=green>OK  </>' : '<fg=red>FAIL</>', $label));

        if (! $ok) {
            $this->failed = true;

            if ($remedy !== '') {
                $this->line('       <fg=yellow>→ '.$remedy.'</>');
            }
        }

        return $ok;
    }

    private function warnIf(bool $condition, string $message): void
    {
        if ($condition) {
            $this->line('  <fg=yellow>WARN</> '.$message);
        }
    }

    private function snippet(string $value, int $limit = 300): string
    {
        return Str::limit(addcslashes(trim(substr($value, 0, $limit + 100)), "\0..\37"), $limit);
    }

    private function section(string $title): void
    {
        $this->newLine();
        $this->line('<fg=cyan;options=bold>'.$title.'</>');
    }
}
