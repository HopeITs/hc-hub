<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class ContractExportController extends Controller
{
    public function pdf(Contract $contract)
    {
        $contract->loadMissing([
            'employee', 'employee.city',
            'position.center', 'position.department', 'position.jobLevel', 'position.program',
            'contractType',
        ]);

        $category   = $contract->contractType?->category ?? 'annual';   // annual|trial|service
        $pagesMap   = ['annual' => 5, 'trial' => 4, 'service' => 4];
        $totalPages = $pagesMap[$category] ?? 1;
        $locale     = app()->getLocale();

        $html = view('livewire.contract.details.print-dompdf', [
            'contract'   => $contract,
            'category'   => $category,   // 'annual' | 'trial' | 'service'
            'totalPages' => $totalPages,
            'locale'     => $locale,
        ])->render();

        $file = sprintf(
            '%s-%s-%s.pdf',
            Str::slug($contract->employee?->name ?? 'contract'),
            $category,
            now()->format('Ymd')
        );

        return Pdf::loadHTML($html)
            ->setPaper('a4', 'portrait')
            ->set_option('isHtml5ParserEnabled', true)
            ->set_option('isRemoteEnabled', true)
            ->set_option('defaultFont', 'somar')  // from config/dompdf.php
            ->stream($file);                       // change to ->download($file) if you want
    }
}
