<?php

namespace Database\Factories;

use App\Enums\FileType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Core\Models\User;
use Modules\Tracking\Models\EvidenceType;
use Modules\Tracking\Models\MonthlyReport;
use Modules\Tracking\Models\MonthlyReportFile;

/**
 * @extends Factory<MonthlyReportFile>
 */
class MonthlyReportFileFactory extends Factory
{
    /**
     * A report-level evidence file row (no file on disk).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'monthly_report_id' => MonthlyReport::factory(),
            'evidence_type_id' => EvidenceType::factory(),
            'path' => 'monthly-report-files/'.Str::random(40).'.pdf',
            'disk' => 'local',
            'original_name' => 'evidence.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'type' => FileType::Pdf,
            'uploaded_by' => User::factory(),
        ];
    }
}
