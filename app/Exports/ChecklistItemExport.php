<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithProperties;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Table;
use PhpOffice\PhpSpreadsheet\Worksheet\Table\TableStyle;
use Illuminate\Support\Collection;
use App\Enums\ChecklistItemOrder;

class ChecklistItemExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithProperties
{
    public function __construct(
        protected Collection $collection,
        protected array $visibleColumnCodes
    ) {
        // FORCE ENGLISH LOCALE
        app()->setLocale('en');
    }

    public function properties(): array
    {
        return [
            'creator'        => 'Hope HR',
            'lastModifiedBy' => ' ', 
            'title'          => ' ', 
            'company'        => 'Hope Center',
        ];
    }

    public function collection(): Collection
    {
        return $this->collection;
    }

    public function headings(): array
    {
        $headers = [];
        
        foreach ($this->visibleColumnCodes as $code) {
            $headers[] = match ($code) {
                'id'    => 'ID',
                'name'  => __('general.name'),
                'order' => __('general.order'),
                default => $code,
            };
        }

        return $headers;
    }

    public function map($row): array
    {
        $mappedRow = [];
        
        foreach ($this->visibleColumnCodes as $code) {
            $mappedRow[] = match ($code) {
                'id'    => $row->id,
                'name'  => $row->name,
                'order' => ChecklistItemOrder::getLocalizedName($row->order)['value'] ?? '', 
                default => '',
            };
        }

        return $mappedRow;
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        $globalRange = 'A1:' . $highestColumn . $highestRow;

        $table = new Table($globalRange, 'ChecklistItems');
        $tableStyle = new TableStyle();
        $tableStyle->setTheme(TableStyle::TABLE_STYLE_LIGHT1); 
        $tableStyle->setShowRowStripes(true);
        $table->setStyle($tableStyle);
        
        $sheet->addTable($table);

        return [
            $globalRange => [
                'font' => [
                    'name' => 'Somar Sans',
                    'size' => 11,
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
            ],
            1 => [
                'font' => [
                    'name'  => 'Somar Sans',
                    'bold'  => true,
                    'size'  => 12,
                    'color' => ['argb' => 'FFFFFFFF'], 
                ],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFC2151C'], 
                ],
            ],
        ];
    }
}







