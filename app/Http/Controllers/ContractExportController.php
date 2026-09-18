<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
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

    private function view(Contract $contract, bool $inlineFonts = false)
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
            'inlineFonts' => $inlineFonts,
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
        // Browsershot's headless Chrome doesn't reliably fetch the
        // @font-face asset() URLs back through the app's own web server, so
        // fonts are inlined as data URIs for this path only.
        $html = $this->view($contract, inlineFonts: true)->render();

        $browsershot = Browsershot::html($html)
            ->setChromePath(config('browsershot.chrome_path'))
          ->timeout(120)
            ->noSandbox()
            ->addChromiumArguments([
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
                'js-flags=--max-old-space-size=256',
            ])
            ->showBackground()
            ->setOption('preferCSSPageSize', true)
            ->waitUntilNetworkIdle();

        if ($nodeBinary = config('browsershot.node_binary')) {
            $browsershot->setNodeBinary($nodeBinary);
        }

   

        // On resource-constrained shared hosting, headless Chrome can be
        // killed intermittently by the account's process/memory limits.
        // Retry a couple of times before giving up.
        $pdf = retry(3, fn () => $browsershot->pdf(), 500);

        $category = $contract->contractType?->category ?? 'annual';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$this->fileName($contract, $category).'"',
        ]);
    }
}
