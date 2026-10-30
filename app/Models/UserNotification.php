<?php

namespace App\Models;

use App\Enums\NotificationKind;
use App\Enums\NotificationPriority;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;

/**
 * One row of a user's inbox, for every module (tracking's extended schema).
 *
 * Extends Laravel's DatabaseNotification, so `$user->notifications()` and the
 * Notifiable helpers keep working, and adds the columns the bell groups,
 * filters and pins on. Rows written before the hub inbox (HR's title/body
 * shape) and rows written with a translation key both render through
 * App\Notifications\NotificationPresenter.
 *
 * @property string $id
 * @property string $type
 * @property string $notifiable_type
 * @property int $notifiable_id
 * @property array<string, mixed> $data
 * @property string|null $search_text
 * @property NotificationPriority $priority
 * @property string|null $module
 * @property NotificationKind $kind
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property int|null $actor_id
 * @property int|null $activity_id
 * @property string|null $group_key
 * @property Carbon|null $snoozed_until
 * @property Carbon|null $read_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class UserNotification extends DatabaseNotification
{
    protected $fillable = [
        'id', 'type', 'notifiable_type', 'notifiable_id', 'data', 'search_text', 'priority', 'module', 'kind',
        'subject_type', 'subject_id', 'actor_id', 'activity_id', 'group_key', 'snoozed_until', 'read_at',
    ];

    /**
     * Mirror the column defaults for rows that have not been round-tripped.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'priority' => 'info',
        'kind' => 'action',
    ];

    /**
     * The user whose action this notification is about.
     *
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * The module the row is filed under; rows a queued job wrote through
     * Laravel's plain shape have none and belong to the hub.
     */
    public function moduleKey(): string
    {
        return $this->module ?? 'hub';
    }

    public function isSnoozed(): bool
    {
        return $this->snoozed_until !== null && $this->snoozed_until->isFuture();
    }

    /**
     * Limit to one user's inbox.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function forUser(Builder $query, User $user): void
    {
        $query->where('notifiable_type', $user->getMorphClass())->where('notifiable_id', $user->getKey());
    }

    /**
     * Hide rows that are still snoozed.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function visible(Builder $query): void
    {
        $query->where(fn (Builder $query) => $query->whereNull('snoozed_until')->orWhere('snoozed_until', '<=', now()));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data' => 'array',
            'priority' => NotificationPriority::class,
            'kind' => NotificationKind::class,
            'notifiable_id' => 'integer',
            'subject_id' => 'integer',
            'actor_id' => 'integer',
            'activity_id' => 'integer',
            'snoozed_until' => 'datetime',
            'read_at' => 'datetime',
        ];
    }
}
