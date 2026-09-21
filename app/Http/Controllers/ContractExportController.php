<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Services\ContractPdfRenderer;
use Illuminate\Support\Facades\Gate;

class ContractExportController extends Controller
{
    public function __construct(private readonly ContractPdfRenderer $renderer) {}

    public function pdf(Contract $contract)
    {
        Gate::authorize('export', $contract);

        $cityId = auth()->user()->activeCityId();
        if ($cityId && $contract->employee?->city_id !== $cityId) {
            abort(403);
        }

        return response($this->renderer->render($contract), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$this->renderer->fileName($contract).'"',
        ]);
    }

    // Local-only: renders the export for any contract without the auth/city
    // gates above, as plain HTML (not through Browsershot), so the print
    // layout can be iterated on quickly in the browser's own devtools.
    public function preview(Contract $contract)
    {
        abort_unless(app()->isLocal(), 404);

        return $this->renderer->view($contract);
    }
}
