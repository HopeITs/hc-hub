<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\User;

/**
 * One person's month of attendance and how far it has got: open, confirmed by the employee,
 * approved by the manager (which approves the overtime), locked by HR. The signed paper is an
 * hr_media row (HrMedia::ATTENDANCE_MONTH, collection "signed").
 *
 * @property int $id
 * @property int $employee_id
 * @property CarbonImmutable $month
 * @property CarbonImmutable|null $employee_confirmed_at
 * @property int|null $employee_confirmed_by
 * @property CarbonImmutable|null $approved_at
 * @property int|null $approved_by
 * @property float $overtime_hours_approved
 * @property CarbonImmutable|null $locked_at
 * @property int|null $locked_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['employee_id', 'month'])]
class AttendanceMonth extends Model
{
    public const OPEN = 'open';

    public const CONFIRMED = 'confirmed';

    public const APPROVED = 'approved';

    public const LOCKED = 'locked';

    /** @var array<string, mixed> */
    protected $attributes = [
        'employee_confirmed_at' => null,
        'employee_confirmed_by' => null,
        'approved_at' => null,
        'approved_by' => null,
        'overtime_hours_approved' => 0,
        'locked_at' => null,
        'locked_by' => null,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'month' => 'date',
            'employee_confirmed_at' => 'datetime',
            'approved_at' => 'datetime',
            'locked_at' => 'datetime',
            'overtime_hours_approved' => 'float',
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
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function locker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    /** Where the month stands. */
    public function state(): string
    {
        return match (true) {
            $this->locked_at !== null => self::LOCKED,
            $this->approved_at !== null => self::APPROVED,
            $this->employee_confirmed_at !== null => self::CONFIRMED,
            default => self::OPEN,
        };
    }

    /** The month's record, not yet saved when nothing has happened to it. */
    public static function for(Employee|int $employee, CarbonImmutable $month): self
    {
        $id = $employee instanceof Employee ? (int) $employee->id : $employee;
        $first = $month->startOfMonth()->toDateString();

        return self::query()->where('employee_id', $id)->whereDate('month', $first)->first()
            ?? new self(['employee_id' => $id, 'month' => $first]);
    }
}
