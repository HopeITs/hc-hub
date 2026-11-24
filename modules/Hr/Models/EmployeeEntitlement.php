<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\User;

/**
 * One person's item of the Permissions Library: required (should be done),
 * granted (done outside the system) or revoked (taken back). `source` says
 * whether it came from the position's defaults or was added by hand.
 *
 * @property int $id
 * @property int $employee_id
 * @property int $entitlement_id
 * @property string $source
 * @property string $state
 * @property int|null $granted_by
 * @property CarbonImmutable|null $granted_at
 * @property int|null $revoked_by
 * @property CarbonImmutable|null $revoked_at
 * @property CarbonImmutable|null $needs_review_at
 * @property string|null $note
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['employee_id', 'entitlement_id', 'source', 'state', 'granted_by', 'granted_at', 'revoked_by', 'revoked_at', 'needs_review_at', 'note'])]
class EmployeeEntitlement extends Model
{
    public const string SOURCE_DEFAULT = 'default';

    public const string SOURCE_MANUAL = 'manual';

    public const string STATE_REQUIRED = 'required';

    public const string STATE_GRANTED = 'granted';

    public const string STATE_REVOKED = 'revoked';

    /** @var array<string, mixed> */
    protected $attributes = ['source' => self::SOURCE_DEFAULT, 'state' => self::STATE_REQUIRED];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'granted_at' => 'datetime',
            'revoked_at' => 'datetime',
            'needs_review_at' => 'datetime',
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
     * @return BelongsTo<Entitlement, $this>
     */
    public function entitlement(): BelongsTo
    {
        return $this->belongsTo(Entitlement::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function revokedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    /**
     * Not taken back: still to do, or done.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn($query->qualifyColumn('state'), [self::STATE_REQUIRED, self::STATE_GRANTED]);
    }
}
