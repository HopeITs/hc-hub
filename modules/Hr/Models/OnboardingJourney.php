<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\User;

/**
 * A new volunteer's onboarding journey (D4-I, DEC-09). Only
 * App\Services\OnboardingJourneys writes it; nothing here is fillable.
 *
 * @property int $id
 * @property int $employee_id
 * @property int|null $contract_id
 * @property mixed $started_at
 * @property CarbonImmutable|null $orientation_due_on
 * @property CarbonImmutable|null $orientation_scheduled_for
 * @property int|null $orientation_scheduled_by
 * @property mixed|null $orientation_completed_at
 * @property int|null $orientation_completed_by
 * @property mixed|null $card_handed_over_at
 * @property int|null $card_handed_over_by
 * @property mixed|null $completed_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class OnboardingJourney extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'orientation_due_on' => 'date',
            'orientation_scheduled_for' => 'date',
            'orientation_completed_at' => 'datetime',
            'card_handed_over_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    protected $guarded = ['*'];

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsTo<Contract, $this>
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function orientationScheduledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'orientation_scheduled_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function orientationCompletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'orientation_completed_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function cardHandedOverBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'card_handed_over_by');
    }

    public function orientationIsLate(): bool
    {
        return $this->orientation_completed_at === null
            && $this->orientation_due_on !== null
            && $this->orientation_due_on->lt(now(config('app.timezone'))->startOfDay());
    }
}
