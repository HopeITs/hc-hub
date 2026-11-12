<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Database\Factories\BadgeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\User;
use Modules\Hr\Support\Employees\HrAccess;

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
    /** @use HasFactory<BadgeFactory> */
    use HasFactory;

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

    /**
     * The badges a user may see: HR within its cities, the general manager
     * and super admin everywhere, a manager for their direct reports.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if (! $user->can('badges.view')) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('employee', fn (Builder $employee) => static::constrainEmployees($employee, $user));
    }

    public static function coversEmployee(User $user, Employee $employee): bool
    {
        return static::constrainEmployees(Employee::query()->whereKey($employee->getKey()), $user)->exists();
    }

    /**
     * @param  Builder<Employee>  $employees
     * @return Builder<Employee>
     */
    public static function constrainEmployees(Builder $employees, User $user): Builder
    {
        if (HrAccess::is($user, 'HR', 'general-manager', 'super-admin')) {
            $cityIds = HrAccess::cityIds($user);

            return $employees->when($cityIds !== null, fn (Builder $query) => $query->whereIn('city_id', $cityIds));
        }

        if (HrAccess::is($user, 'manager')) {
            return $employees->whereHas('allContracts', fn (Builder $contract) => $contract->visibleTo($user));
        }

        return $employees->whereRaw('1 = 0');
    }
}
