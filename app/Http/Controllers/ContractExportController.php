<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Support\ArabicText as AR;
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

        $category   = $contract->contractType?->category ?? 'annual';
        $pagesMap   = ['annual' => 4, 'trial' => 4, 'service' => 4];
        $totalPages = $pagesMap[$category] ?? 1;
        $locale     = app()->getLocale();
        $dir        = $locale === 'ar' ? 'rtl' : 'ltr';

        $viewData = [
            'contract'   => $contract,
            'category'   => $category,
            'totalPages' => $totalPages,
            'locale'     => $locale,
            'dir'        => $dir,
            'forPdf'     => true,
        ];

        $pdf = Pdf::loadView('livewire.contract.export.print-dompdf', $viewData)
            ->setPaper('a4', 'portrait')
            ->set_option('isHtml5ParserEnabled', true)
            ->set_option('isRemoteEnabled', true)
            ->set_option('isFontSubsettingEnabled', true)
            ->setWarnings(false);


        $file = sprintf(
            '%s-%s-%s.pdf',
            Str::slug($contract->employee?->name ?? 'contract'),
            $category,
            now()->format('Ymd')
        );

        return $pdf->stream($file);
    }

}
