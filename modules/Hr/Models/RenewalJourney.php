<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\User;

/**
 * The renewal journey of an expiring contract (DEC-09 increment 2,
 * docs/ux/journeys.md §3.2). Only App\Services\RenewalJourneys writes it;
 * nothing here is fillable.
 *
 * @property int $id
 * @property int $contract_id
 * @property int $employee_id
 * @property mixed $started_at
 * @property string|null $decision
 * @property int|null $decided_by
 * @property mixed|null $decided_at
 * @property bool $decision_switched_auto_renew
 * @property mixed|null $completed_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class RenewalJourney extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'decided_at' => 'datetime',
            'decision_switched_auto_renew' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    public const DECISION_RENEW = 'renew';

    public const DECISION_STOP = 'stop';

    public const DECISIONS = [self::DECISION_RENEW, self::DECISION_STOP];

    protected $guarded = ['*'];

    /**
     * @return BelongsTo<Contract, $this>
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
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
    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
