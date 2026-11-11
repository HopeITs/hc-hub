<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\User;
use Modules\Hr\Enums\NotificationCategory;

/**
 * A push or email still to send for one notification, and the earliest time
 * it may go (quiet hours, working days and digests move it later).
 * channel is 'push' or 'email'; payload is {title, body, url} with title and
 * body as {en, ar} arrays (or plain strings), as the notification stored them.
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $notification_id
 * @property string $channel
 * @property string $category
 * @property bool $urgent
 * @property array<mixed> $payload
 * @property mixed $deliver_after
 * @property mixed|null $sent_at
 * @property mixed|null $failed_at
 * @property int $attempts
 * @property string|null $error
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['user_id', 'notification_id', 'channel', 'category', 'urgent', 'payload', 'deliver_after'])]
class NotificationOutboxEntry extends Model
{
    public const PUSH = 'push';

    public const EMAIL = 'email';

    protected $table = 'notification_outbox';

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

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function categoryEnum(): NotificationCategory
    {
        return NotificationCategory::tryFrom($this->category) ?? NotificationCategory::System;
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeDue(Builder $query): Builder
    {
        return $query->whereNull('sent_at')->whereNull('failed_at')->where('deliver_after', '<=', now());
    }
}
