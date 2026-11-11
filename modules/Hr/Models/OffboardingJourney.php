<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\User;

/**
 * A departing volunteer's exit journey (DEC-09 increment 2). Only
 * App\Services\OffboardingJourneys writes it; nothing here is fillable.
 *
 * @property int $id
 * @property int $employee_id
 * @property int|null $contract_id
 * @property string $trigger
 * @property mixed $started_at
 * @property int|null $started_by
 * @property mixed|null $exit_interview_held_at
 * @property int|null $exit_interview_by
 * @property mixed|null $completed_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class OffboardingJourney extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'exit_interview_held_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public const TRIGGER_AUTO_RENEW_STOPPED = 'auto_renew_stopped';

    public const TRIGGER_TERMINATED = 'terminated';

    public const TRIGGER_CONTRACT_ENDED = 'contract_ended';

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
    public function startedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function exitInterviewBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'exit_interview_by');
    }
}
