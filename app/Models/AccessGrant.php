<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Access\AccessScope;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\AccessGrantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\User;

/**
 * A role or one permission given to a user on one scope (a project, a
 * program, a city) for a time. It counts from starts_at (null: at once)
 * until ends_at (null: open-ended), and never once revoked_at is set. Rows
 * are not deleted. See App\Support\Access\AccessScope for how it is read.
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $role_id
 * @property int|null $permission_id
 * @property string $scope_type
 * @property int $scope_id
 * @property CarbonImmutable|null $starts_at
 * @property CarbonImmutable|null $ends_at
 * @property int|null $granted_by
 * @property int|null $delegated_from_user_id
 * @property CarbonImmutable|null $revoked_at
 * @property int|null $revoked_by
 * @property string|null $reason
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['user_id', 'role_id', 'permission_id', 'scope_type', 'scope_id', 'starts_at', 'ends_at', 'granted_by', 'delegated_from_user_id', 'revoked_at', 'revoked_by', 'reason'])]
class AccessGrant extends Model
{
    /** @use HasFactory<AccessGrantFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        // What a user holds through grants is read once per request.
        static::saved(fn () => app(AccessScope::class)->forget());
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * Grants that count at the given moment: started, not ended, not revoked.
     * A grant ends at ends_at exactly: from that moment on it gives nothing.
     *
     * @param  Builder<$this>  $query
     */
    #[Scope]
    protected function activeAt(Builder $query, ?CarbonInterface $moment = null): void
    {
        $moment ??= now();

        $query->whereNull('revoked_at')
            ->where(fn (Builder $query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', $moment))
            ->where(fn (Builder $query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', $moment));
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * @return BelongsTo<Permission, $this>
     */
    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class);
    }
}
