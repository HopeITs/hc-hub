<?php

namespace App\Exports;

use App\Models\Contract;
use App\Enums\SalaryPer;
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

class ContractExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithProperties
{
    public function __construct(
        protected Collection $collection,
        protected array $visibleColumnCodes
    ) {
        
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
                'id'                                                         => 'ID',
                'employee'                                                   => trans_choice('general.employee', 1, [], 'en'),
                'position_title_en', 'position_title_ar'                     => trans_choice('general.positions', 1, [], 'en'),
                'contractType_name_en', 'contractType_name_ar'               => trans_choice('general.contract-type', 1, [], 'en'),
                'position_department_name'                                   => trans_choice('general.department', 1, [], 'en'),
                'position_jobLevel_name_en', 'position_jobLevel_name_ar'     => trans_choice('general.job-level', 1, [], 'en'),
                'duration'                                                   => __('general.duration', [], 'en'),
                'period'                                                     => __('general.period', [], 'en'),
                'leaveBalances'                                              => __('general.yearly-leave-days', [], 'en'),
                'salary_per'                                                 => __('general.salary-per', [], 'en'),
                'weekly_hours'                                               => __('general.weekly-hours', [], 'en'),
                'is_active'                                                  => __('general.status', [], 'en'),
                default                                                      => $code,
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
                'id'                                                         => $row->id,
                'employee'                                                   => $row->employee?->name ?? '—',
                'position_title_en', 'position_title_ar'                     => $row->position?->title_en ?? '—',
                'contractType_name_en', 'contractType_name_ar'               => $row->contractType?->name_en ?? '—',
                'position_department_name'                                   => $row->position?->department?->name ?? '—',
                'position_jobLevel_name_en', 'position_jobLevel_name_ar'     => $row->position?->jobLevel?->name_en ?? '—',
                'duration'                                                   => $row->duration . ' ' . __('general.months', [], 'en'),
                'period'                                                     => ($row->start_date?->format('Y-m-d') ?? '') . ' to ' . ($row->end_date?->format('Y-m-d') ?? ''),
                'leaveBalances'                                              => strip_tags($row->leaveBalances->first()?->deserved_formatted ?? '0'),
                'salary_per'                                                 => SalaryPer::getLocalizedName($row->salary_per instanceof SalaryPer ? $row->salary_per->value : (int)$row->salary_per)['value'] ?? '',
                'weekly_hours'                                               => $row->weekly_hours ?? '0',
                'is_active'                                                  => $row->is_active ? 'Yes' : 'No',
                default                                                      => '',
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

        $table = new Table($globalRange, 'Contracts');
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