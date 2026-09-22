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
            // Encode the raw HTML to send directly to the remote browser
            $dataUri = 'data:text/html;base64,'.base64_encode($html);

            $this->browsershot($dataUri)->savePdf($target);

            $pdf = is_file($target) ? (string) file_get_contents($target) : '';
        } finally {
            File::delete($target);
        }

        if (! str_starts_with($pdf, '%PDF-')) {
            throw new RuntimeException('Chrome did not write a PDF.');
        }

        return $pdf;
    }

    public function browsershot(string $url): InspectableBrowsershot
    {
        // Use url() instead of html()
        $browsershot = InspectableBrowsershot::url($url)
            ->setWSEndpoint('wss://chrome.browserless.io?token=2VJ1USXCdNLtLvgf1e3abe75651f22d0b213f525d41f26729')
            ->timeout((int) config('browsershot.timeout'))
            ->waitUntilNetworkIdle()
            ->ignoreHttpsErrors()
            ->disableCaptureURLS()
            ->showBackground()
            ->setOption('preferCSSPageSize', true);

        if ($nodeBinary = config('browsershot.node_binary')) {
            $browsershot->setNodeBinary($nodeBinary);
        }

        return $browsershot;
    }
}
