<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\User;

/**
 * The answer for a working day with no record (no punch, no leave, no holiday) or a single punch:
 * the person worked, or was absent without leave. Leave itself is recorded in the leave module.
 *
 * @property int $id
 * @property int $employee_id
 * @property CarbonImmutable $work_date
 * @property string $kind
 * @property string|null $note
 * @property int|null $decided_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['employee_id', 'work_date', 'kind', 'note', 'decided_by'])]
class AttendanceDayDecision extends Model
{
    public const WORKED = 'worked';

    public const ABSENT = 'absent';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['work_date' => 'date'];
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
