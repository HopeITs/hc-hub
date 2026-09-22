<?php

namespace App\Services;

use App\Models\Contract;
use App\Support\InspectableBrowsershot;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Renders a contract to PDF through a headless Chrome driven by Browsershot.
 *
 * Point BROWSERSHOT_CHROME_PATH at the host's own Chromium (on a Linux server,
 * usually /usr/bin/chromium from the package manager, whose dependencies the
 * package manager has already satisfied) and BROWSERSHOT_NODE_BINARY at node if
 * it is not on the web user's PATH. `contracts:export-doctor` drives this same
 * class and reports what the host does with it.
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
     * @throws RuntimeException
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
        $target = $this->workDir().'/'.Str::random(16).'.pdf';
        $pdf = '';

        try {
            // Chrome writes the PDF to disk itself. pdf() would instead have it
            // base64-ed back through node's stdout, and Browsershot treats a
            // stdout it cannot parse as a success — it decodes null and returns
            // an empty string, which is how a broken render used to reach the
            // browser as a 200 with a 0-byte body.
            $this->browsershot($html)->savePdf($target);

            $pdf = is_file($target) ? (string) file_get_contents($target) : '';
        } finally {
            File::delete($target);
        }

        if (! str_starts_with($pdf, '%PDF-')) {
            throw new RuntimeException('Chrome did not write a PDF.');
        }

        return $pdf;
    }
public function browsershot(string $html): InspectableBrowsershot
    {
        $browsershot = InspectableBrowsershot::html($html)
            ->setWSEndpoint('wss://chrome.browserless.io?token=2VJ1USXCdNLtLvgf1e3abe75651f22d0b213f525d41f26729')
            ->timeout((int) config('browsershot.timeout'))
            ->waitUntilNetworkIdle()
            ->ignoreHttpsErrors()
            ->disableCaptureURLS()
            ->showBackground()
            ->setOption('preferCSSPageSize', true);

        // Optional: You can also safely remove setNodeBinary
        // since the remote WebSocket handles the heavy lifting,
        // but keeping it won't break anything.
        if ($nodeBinary = config('browsershot.node_binary')) {
            $browsershot->setNodeBinary($nodeBinary);
        }

        return $browsershot;
    }

    /**
     * The three switches a locked-down server needs, and no more.
     *
     * Each answers a line the host puts in Chrome's own log:
     *
     *   pcilib: Cannot open /sys/bus/pci/devices/…: Permission denied
     *   GPU process exited unexpectedly: exit_code=256   (six times)
     *   FATAL: GPU process isn't usable. Goodbye.
     *
     * The GPU process identifies the card through libpci, and a host that hides
     * /sys/bus/pci kills it every time; after six deaths Chrome aborts the whole
     * browser. --in-process-gpu leaves no separate process to count. Deliberately
     * absent: --disable-software-rasterizer, which would take away the SwiftShader
     * fallback that makes running without a GPU work at all.
     *
     * @return array<int, string>
     */
    public function chromiumArguments(): array
    {
        return [
            'disable-gpu',
            'in-process-gpu',
            // /dev/shm is commonly present but read-only on these hosts; this
            // sends Chrome's shared memory to /tmp instead.
            'disable-dev-shm-usage',
        ];
    }

    /**
     * A directory the web user owns, for Chrome's config and the rendered file.
     */
    public function workDir(): string
    {
        $dir = storage_path('app/chrome');

        File::ensureDirectoryExists($dir.'/.config', 0755, true);

        return $dir;
    }
}
