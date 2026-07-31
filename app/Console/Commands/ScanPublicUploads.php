<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Incident-response sweep of publicly served storage.
 *
 * The program-logo upload previously wrote files under the client's own
 * extension into storage/app/public, so this command exists to answer the
 * question "was that ever exploited before the fix shipped?" Run it once on
 * every environment that served the vulnerable code, then keep it in the
 * release checklist as a cheap regression detector.
 */
class ScanPublicUploads extends Command
{
    protected $signature = 'security:scan-uploads
        {--disk=public : The filesystem disk to sweep}
        {--days=0 : Also report every file modified within this many days}';

    protected $description = 'Report executable, double-extension, or otherwise suspicious files in public storage';

    /** Extensions that must never exist under a publicly served path. */
    private const EXECUTABLE = [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phps', 'phar',
        'pht', 'cgi', 'pl', 'py', 'rb', 'sh', 'bash', 'asp', 'aspx', 'jsp',
        'jspx', 'exe', 'dll', 'so', 'htaccess', 'htpasswd',
    ];

    /** Only these may be served as user-uploaded content. */
    private const EXPECTED = ['jpg', 'jpeg', 'png', 'webp', 'svg', 'pdf', 'ico', 'gitignore', 'htaccess'];

    public function handle(): int
    {
        $disk = Storage::disk($this->option('disk'));
        $days = (int) $this->option('days');
        $findings = 0;
        $recent = [];

        foreach ($disk->allFiles() as $path) {
            $name = basename($path);
            $parts = array_map('strtolower', explode('.', $name));
            $extension = end($parts);
            // Every dot-segment after the first, so logo.php.jpg is inspected
            // for "php" and not only for its trailing "jpg".
            $inner = array_slice($parts, 1);

            $executable = array_intersect($inner, self::EXECUTABLE);

            if ($executable !== []) {
                $findings++;
                $this->error(sprintf(
                    'EXECUTABLE  %s  (matched: %s)',
                    $path,
                    implode(', ', $executable),
                ));

                continue;
            }

            if (count($inner) > 1) {
                $findings++;
                $this->warn("DOUBLE-EXT  {$path}");

                continue;
            }

            if (! in_array($extension, self::EXPECTED, true)) {
                $findings++;
                $this->warn("UNEXPECTED  {$path}  (.{$extension})");

                continue;
            }

            // A file whose leading bytes are a PHP open tag is a polyglot or a
            // renamed script regardless of what the extension claims.
            $head = (string) substr((string) $disk->get($path), 0, 4096);

            if (str_contains(strtolower($head), '<?php') || str_contains($head, '<?=')) {
                $findings++;
                $this->error("PHP CONTENT {$path}");

                continue;
            }

            if ($days > 0 && $disk->lastModified($path) >= now()->subDays($days)->getTimestamp()) {
                $recent[] = $path;
            }
        }

        foreach ($recent as $path) {
            $this->line("RECENT      {$path}");
        }

        if ($findings === 0) {
            $this->info('No suspicious files found in public storage.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->error("{$findings} suspicious file(s) found. Quarantine them, then check the web-server access log for requests to each path before deleting.");

        return self::FAILURE;
    }
}
