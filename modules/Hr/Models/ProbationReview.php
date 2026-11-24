<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\User;

/**
 * The review of a trial contract in its last third. Only
 * Modules\Hr\Support\Contracts\ProbationReviews writes it; nothing here is
 * fillable. The decision is a request to HR, not a change to the contract.
 *
 * @property int $id
 * @property int $contract_id
 * @property int $employee_id
 * @property CarbonImmutable $started_at
 * @property string|null $decision
 * @property string|null $reason
 * @property int|null $decided_by
 * @property CarbonImmutable|null $decided_at
 * @property CarbonImmutable|null $hr_seen_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class ProbationReview extends Model
{
    public const string EXTEND = 'extend';

    public const string CONFIRM = 'confirm';

    public const string CANCEL = 'cancel';

    /** @var list<string> */
    public const array DECISIONS = [self::EXTEND, self::CONFIRM, self::CANCEL];

    protected $guarded = ['*'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'decided_at' => 'datetime',
            'hr_seen_at' => 'datetime',
        ];
    }

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

    public function isDecided(): bool
    {
        return $this->decision !== null;
    }
}
