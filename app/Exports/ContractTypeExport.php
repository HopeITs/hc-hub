<?php /*

namespace App\Exports;

use App\Enums\ContractPlace;
use App\Enums\ContractType as ContractTypeEnum;
use App\Models\ContractType;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\Console\Helper\TableStyle;

class ContractTypeExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function __construct(
        protected Collection $collection
    ) {}

    public function collection(): Collection
    {
        return $this->collection;
    }

    public function headings(): array
    {
        return [
            'ID',
            __('general.name') . ' (AR)',
            __('general.name') . ' (EN)',
            __('general.place'),
            __('general.type'),
            __('general.created-at'), 
        ];
    }

    public function map($row): array
    {
        return [
            $row->id,
            $row->name_ar,
            $row->name_en,
            ContractPlace::getLocalizedName($row->place)['value'] ?? '',
            ContractTypeEnum::getLocalizedName($row->type)['value'] ?? '',
            $row->created_at ? $row->created_at->format('Y-m-d H:i') : '',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
     
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

 
}
*/


// namespace App\Exports;

// use App\Models\ContractType;
// use Maatwebsite\Excel\Concerns\FromCollection;
// use Maatwebsite\Excel\Concerns\WithHeadings;
// use Maatwebsite\Excel\Concerns\WithMapping;
// use Maatwebsite\Excel\Concerns\ShouldAutoSize;
// use Maatwebsite\Excel\Concerns\WithStyles;
// use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
// use Illuminate\Support\Collection;
// use App\Enums\ContractPlace;
// use App\Enums\ContractType as ContractTypeEnum;

// class ContractTypeExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
// {
//     public function __construct(
//         protected Collection $collection,
//         protected array $visibleColumns // <-- ADDED: Accepts the dynamic columns from the table
//     ) {}

//     public function collection(): Collection
//     {
//         return $this->collection;
//     }

//     public function headings(): array
//     {
//         // Return exactly the column labels that are currently visible on the frontend
//         return $this->visibleColumns;
//     }

//     public function map($row): array
//     {
//         // 1. Map all potential data to their exact translated column labels
//         $allData = [
//             'ID' => $row->id,
//             __('general.name') . ' (AR)' => $row->name_ar,
//             __('general.name') . ' (EN)' => $row->name_en,
//             __('general.place') => ContractPlace::getLocalizedName($row->place)['value'] ?? '',
//             __('general.type') => ContractTypeEnum::getLocalizedName($row->type)['value'] ?? '',
//             __('general.actions') => '', // Usually hidden, but prevents errors if passed
//         ];

//         // 2. Loop through ONLY the visible columns and build the row in that exact order
//         $mappedRow = [];
//         foreach ($this->visibleColumns as $columnLabel) {
//             $mappedRow[] = $allData[$columnLabel] ?? '';
//         }

//         return $mappedRow;
//     }

//     public function styles(Worksheet $sheet): array
//     {
//         // Make the first row (headers) bold
//         return [
//             1 => ['font' => ['bold' => true]],
//         ];
//     }
// }




namespace App\Exports;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Table;
use PhpOffice\PhpSpreadsheet\Worksheet\Table\TableStyle;
use Illuminate\Support\Collection;
use App\Enums\ContractPlace;
use App\Enums\ContractType as ContractTypeEnum;

class ContractTypeExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function __construct(
        protected Collection $collection,
        protected array $visibleColumnCodes // Now accepts codes instead of labels
    ) {
        // FORCE ENGLISH LOCALE for the duration of the export
        // This ensures the __('general.x') and Enums resolve to English
        app()->setLocale('en');
    }

    public function collection(): Collection
    {
        return $this->collection;
    }

    public function headings(): array
    {
        $headers = [];
        
        // Map the codes back to forced English headers
        foreach ($this->visibleColumnCodes as $code) {
            $headers[] = match ($code) {
                'id'      => 'ID',
                'name_ar' => __('general.name') . ' (AR)',
                'name_en' => __('general.name') . ' (EN)',
                'place'   => __('general.place'),
                'type'    => __('general.type'),
                default   => $code,
            };
        }

        return $headers;
    }

    public function map($row): array
    {
        $mappedRow = [];
        
        // Safely map data using the column code, ignoring UI locale
        foreach ($this->visibleColumnCodes as $code) {
            $mappedRow[] = match ($code) {
                'id'      => $row->id,
                'name_ar' => $row->name_ar,
                'name_en' => $row->name_en,
                'place'   => ContractPlace::getLocalizedName($row->place)['value'] ?? '', // Will be EN
                'type'    => ContractTypeEnum::getLocalizedName($row->type)['value'] ?? '', // Will be EN
                default   => '',
            };
        }

        return $mappedRow;
    }

// public function styles(Worksheet $sheet): array
//     {
//         $highestRow = $sheet->getHighestRow();
//         $highestColumn = $sheet->getHighestColumn();
//         $globalRange = 'A1:' . $highestColumn . $highestRow;

//         return [
//             // 1. Global styles for ALL CELLS (Headers + Data Rows)
//             $globalRange => [
//                 'font' => [
//                     'name' => 'Somar Sans',
//                     'size' => 11,
//                 ],
//                 'alignment' => [
//                     'horizontal' => Alignment::HORIZONTAL_CENTER,
//                     'vertical'   => Alignment::VERTICAL_CENTER,
//                 ],
//                 'borders' => [
//                     'allBorders' => [
//                         'borderStyle' => Border::BORDER_THIN,
//                     ],
//                 ],
//             ],
//             // 2. Specific styles overriding the FIRST ROW (Headers)
//             1 => [
//                 'font' => [
//                     'name'  => 'Somar Sans',
//                     'bold'  => true,
//                     'size'  => 12,
//                     'color' => ['argb' => 'FFFFFFFF'], // White text so it's readable
//                 ],
//                 'fill' => [
//                     'fillType'   => Fill::FILL_SOLID,
//                     'startColor' => ['argb' => 'FFC2151C'], // Red background!
//                 ],
//             ],
//         ];
//     }
// }
public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        $globalRange = 'A1:' . $highestColumn . $highestRow;

        // --- NEW: FORMAT AS NATIVE EXCEL TABLE ---
        // Create a table over the entire data range
        $table = new Table($globalRange, 'ContractTypes');
        
        // Create a table style
        $tableStyle = new TableStyle();
        // Use a LIGHT theme so it adds banded rows/filters but keeps your red header!
        $tableStyle->setTheme(TableStyle::TABLE_STYLE_LIGHT1); 
        $tableStyle->setShowRowStripes(true); // Enables alternating banded rows
        $table->setStyle($tableStyle);
        
        // Add the table to the worksheet
        $sheet->addTable($table);
        // -----------------------------------------

        // Return your existing styles to keep the font, alignment, and red background
        return [
            // 1. Global styles for ALL CELLS
            $globalRange => [
                'font' => [
                    'name' => 'Somar Sans',
                    'size' => 11,
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
            ],
            // 2. Specific styles overriding the FIRST ROW (Headers)
            1 => [
                'font' => [
                    'name'  => 'Somar Sans',
                    'bold'  => true,
                    'size'  => 12,
                    'color' => ['argb' => 'FFFFFFFF'], // White text
                ],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFC2151C'], // Your red background
                ],
            ],
        ];
    }
}