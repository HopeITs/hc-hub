<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $employee_id
 * @property int|null $evaluation_id
 * @property int|null $currency
 * @property string $type
 * @property CarbonImmutable $date
 * @property string|null $reason
 * @property string|null $current_salary
 * @property string|null $new_salary
 * @property string|null $notes
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property string|null $subtype
 * @property string|null $amount
 * @property string|null $new_position
 * @property string|null $leave_unit
 */
#[Fillable(['employee_id', 'evaluation_id', 'currency', 'type', 'subtype', 'date', 'reason', 'amount', 'new_position', 'leave_unit', 'current_salary', 'new_salary', 'notes'])]
class Badge extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'date' => 'datetime:d-m-Y',
        ];
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsTo<Evaluation, $this>
     */
    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class);
    }
}
