<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use Barryvdh\Snappy\Facades\SnappyPdf as PDF;
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

        $category   = $contract->contractType?->category ?? 'annual'; // annual|trial|service
        $pagesMap   = ['annual' => 5, 'trial' => 4, 'service' => 4];
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

        $pdf = PDF::loadView('livewire.contract.details.print-snappy', $viewData)
            ->setOption('encoding', 'UTF-8')
            ->setOption('orientation', 'Portrait')
            ->setOption('margin-top', 0)
            ->setOption('margin-bottom', 0)
            ->setOption('margin-left', 0)
            ->setOption('margin-right', 0)
            ->setOption('print-media-type', true)
            ->setOption('enable-local-file-access', true)
            ->setOption('dpi', 96)
            ->setOption('image-dpi', 96)
            ->setOption('disable-smart-shrinking', true);

        $file = sprintf(
            '%s-%s-%s.pdf',
            Str::slug($contract->employee?->name ?? 'contract'),
            $category,
            now()->format('Ymd')
        );

        return $pdf->stream($file);
    }
}
