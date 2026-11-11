<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The working day taken instead of a birthday that is not one (H-4).
 *
 * @property int $id
 * @property int $holiday_id
 * @property int $employee_id
 * @property int $year
 * @property CarbonImmutable $chosen_date
 * @property int $status
 * @property int|null $decided_by
 * @property mixed|null $decided_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['holiday_id', 'employee_id', 'year', 'chosen_date', 'status', 'decided_by', 'decided_at'])]
class BirthdayHolidayChoice extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'chosen_date' => 'date',
            'decided_at' => 'datetime',
            'status' => 'integer',
            'year' => 'integer',
        ];
    }

    public const PENDING = 0;

    public const APPROVED = 1;

    public const REJECTED = 2;

    /**
     * @return BelongsTo<Holiday, $this>
     */
    public function holiday(): BelongsTo
    {
        return $this->belongsTo(Holiday::class);
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
