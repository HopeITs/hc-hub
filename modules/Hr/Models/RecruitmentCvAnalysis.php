<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The automatic reading of one CV: facts only, never the text. A reading
 * aid for the reviewer; nothing ranks or filters by it.
 *
 * @property int $id
 * @property int $file_id
 * @property int|null $application_id
 * @property string $status
 * @property string|null $extraction_method
 * @property int $text_chars
 * @property array<mixed>|null $facts_json
 * @property array<mixed>|null $comparison_json
 * @property string $analyzer_version
 * @property string|null $error
 * @property mixed $analyzed_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class RecruitmentCvAnalysis extends Model
{
    public const COMPLETED = 'completed';

    /**
     * Nothing readable came out, even after OCR (an image-only file, or OCR is off).
     */
    public const NO_TEXT = 'no_text';

    /**
     * A tool this file needs (pdftotext, tesseract, antiword) is not installed.
     */
    public const TOOL_MISSING = 'tool_missing';

    public const UNSUPPORTED = 'unsupported';

    public const FAILED = 'failed';

    protected $table = 'recruitment_cv_analyses';

    protected $guarded = ['id'];

    /**
     * @return BelongsTo<RecruitmentApplicantFile, $this>
     */
    public function file(): BelongsTo
    {
        return $this->belongsTo(RecruitmentApplicantFile::class, 'file_id');
    }

    /**
     * @return BelongsTo<RecruitmentApplication, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(RecruitmentApplication::class, 'application_id');
    }

    public function isCompleted(): bool
    {
        return $this->status === self::COMPLETED;
    }

    protected function casts(): array
    {
        return [
            'facts_json' => 'array',
            'comparison_json' => 'array',
            'analyzed_at' => 'datetime',
        ];
    }
}
