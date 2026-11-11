<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;
use Modules\Core\Models\User;

/**
 * One row per sensitive act on applicant data: who did it, when, under which
 * scope and filter, and to how many records.
 *
 * Written once and never changed. update() and delete() are refused here so no
 * code path can quietly rewrite what was done; the rows hold identifiers and
 * counts, never the data that was exported.
 *
 * @property int $id
 * @property string $action
 * @property string $outcome
 * @property int|null $actor_user_id
 * @property string|null $actor_role
 * @property string $scope_type
 * @property array<mixed>|null $scope_city_ids
 * @property array<mixed>|null $filters_json
 * @property int $record_count
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property string|null $reason
 * @property array<mixed>|null $details_json
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property mixed $created_at
 */
class RecruitmentPrivacyAudit extends Model
{
    public const UPDATED_AT = null;

    public const ACTION_EXPORT = 'export';

    public const ACTION_BULK_DOWNLOAD = 'bulk_download';

    public const ACTION_ERASURE_REQUEST = 'erasure_request';

    public const ACTION_ANONYMIZATION = 'anonymization';

    public const OUTCOME_SUCCESS = 'success';

    /**
     * Attempted and refused: permission, scope, size or rate.
     */
    public const OUTCOME_REFUSED = 'refused';

    /**
     * Recorded but not carried out yet, because something stands in the way.
     */
    public const OUTCOME_BLOCKED = 'blocked';

    /**
     * The actor of an act nobody performed by hand: the retention sweep.
     */
    public const SCOPE_SYSTEM = 'system';

    protected $guarded = ['id'];

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new LogicException('Privacy audit rows are append-only.');
        });

        static::deleting(function (): void {
            throw new LogicException('Privacy audit rows are append-only.');
        });
    }

    protected function casts(): array
    {
        return [
            'scope_city_ids' => 'array',
            'filters_json' => 'array',
            'details_json' => 'array',
            'record_count' => 'integer',
            'created_at' => 'datetime',
        ];
    }
}
