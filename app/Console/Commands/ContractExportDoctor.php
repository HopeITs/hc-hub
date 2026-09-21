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
            $this->check('chrome_path executable', is_executable($chrome), "chmod +x {$chrome}, or the web user cannot run it.");
            $this->probe([$chrome, '--version'], 'chrome --version');
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
            $this->check('PDF produced', $ok, 'Chrome did not write a usable PDF — the stderr above is the reason.');

            if ($ok && $this->option('keep')) {
                $kept = storage_path('app/export-doctor.pdf');
                File::ensureDirectoryExists(dirname($kept));
                File::copy($target, $kept);
                $this->line('  saved          '.$kept);
            }

            return $ok && ! $this->failed;
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
     * @param  array<int, string>  $command
     * @param  array<string, string>  $env
     * @return array{ok: bool, out: string}
     */
    private function probe(array $command, string $label, ?string $cwd = null, array $env = []): array
    {
        try {
            $process = new Process($command, $cwd, $env === [] ? null : $env);
            $process->setTimeout(30);
            $process->run();

            $out = trim($process->getOutput().' '.$process->getErrorOutput());
            $ok = $process->isSuccessful();

            $this->line(sprintf('  %s %s %s', $ok ? '<fg=green>OK  </>' : '<fg=red>FAIL</>', $label, $this->snippet($out)));

            return ['ok' => $ok, 'out' => $out];
        } catch (Throwable $e) {
            $this->line(sprintf('  <fg=red>FAIL</> %s %s', $label, $e->getMessage()));

            return ['ok' => false, 'out' => ''];
        }
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

    private function snippet(string $value): string
    {
        return Str::limit(addcslashes(trim(substr($value, 0, 400)), "\0..\37"), 300);
    }

    private function section(string $title): void
    {
        $this->newLine();
        $this->line('<fg=cyan;options=bold>'.$title.'</>');
    }
}
