<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\User;

/**
 * A push or email copy of one notification still to send, and the earliest
 * moment it may go (quiet hours, working days and digests move it later).
 * Ported from HR.
 *
 * `category` is the notification module. `payload` is the untranslated
 * notification ({module, priority, key, params, link}); it is rendered in the
 * reader's language only when sent (App\Notifications\Delivery\OutboxDispatcher).
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $notification_id
 * @property string $channel
 * @property string $category
 * @property string|null $group_key
 * @property bool $urgent
 * @property array<string, mixed> $payload
 * @property CarbonImmutable $deliver_after
 * @property CarbonImmutable|null $sent_at
 * @property CarbonImmutable|null $failed_at
 * @property int $attempts
 * @property string|null $error
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['user_id', 'notification_id', 'channel', 'category', 'group_key', 'urgent', 'payload', 'deliver_after'])]
class NotificationOutboxEntry extends Model
{
    public const string PUSH = 'push';

    public const string EMAIL = 'email';

    protected $table = 'notification_outbox';

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Not sent, not given up, and its moment has come.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeDue(Builder $query): Builder
    {
        return $query->whereNull('sent_at')->whereNull('failed_at')->where('deliver_after', '<=', now());
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'urgent' => 'boolean',
            'payload' => 'array',
            'deliver_after' => 'datetime',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }
}
