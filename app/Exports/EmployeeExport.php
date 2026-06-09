<?php

namespace App\Exports;

use App\Models\Employee;
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

class EmployeeExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithProperties
{
    public function __construct(
        protected Collection $collection,
        protected array $visibleColumnCodes
    ) {}

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
                'id'                                                           => 'ID',
                'name_en', 'name_ar'                                   => __('general.name', [], 'en'),
                'account'                                                      => trans_choice('general.role', 1, [], 'en'),
                'years_of_service_formatted'                                   => __('general.years-of-service', [], 'en'),
                'join_at'                                                      => __('general.join-at', [], 'en'),
                'city_name_en', 'city_name_ar'                                 => trans_choice('general.city', 1, [], 'en'),
                'direct_manager'                                               => __('general.direct_manager', [], 'en'),
                'tech_manager'                                                 => __('general.tech_manager', [], 'en'),
                'contracts_position_title_en', 'contracts_position_title_ar'   => __('general.position', [], 'en'),
                'work_email'                                                   => __('general.work_email', [], 'en'),
                'is_active'                                                    => __('general.is-active', [], 'en'),
                default                                                        => $code,
            };
        }

        return $headers;
    }

    public function map($row): array
    {

        $orig = app()->getLocale();
        app()->setLocale('en');

        $mappedRow = [];

        foreach ($this->visibleColumnCodes as $code) {
            $mappedRow[] = match ($code) {
                'id'                                                           => $row->id,
                'name_en', 'name_ar'                                           => $row->name,
                'account'                                                      => $row->account?->myRole() ?? '—',
                'years_of_service_formatted'                                   => $row->years_of_service_formatted,
                'join_at'                                                      => $row->join_at,
                'city_name_en', 'city_name_ar'                                 => $row->city?->name_en ?? '—',
                'direct_manager'                                               => $row->latestActiveContract()?->position->directManager?->employee()?->name ?? '—',
                'tech_manager'                                                 => $row->latestActiveContract()?->position->techManager?->employee()?->name ?? '—',
                'contracts_position_title_en', 'contracts_position_title_ar'   => $row->latestActiveContract()?->position?->title_en ?? '—',
                'work_email'                                                   => $row->account?->email ?? '—',
                'is_active'                                                    => $row->is_active ? 'Yes' : 'No',
                default                                                        => '',
            };
        }


        app()->setLocale($orig);

        return $mappedRow;
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        $globalRange = 'A1:' . $highestColumn . $highestRow;

        $table = new Table($globalRange, 'Employees');
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
