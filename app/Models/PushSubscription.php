<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NotificationChannel;
use Carbon\CarbonImmutable;
use Database\Factories\PushSubscriptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\User;

/**
 * One browser or installed app that accepted Web Push for a user (ported
 * from HR). The endpoint and keys are the device's secret: hidden from
 * serialisation, only WebPushSender reads them. The endpoint is unique by
 * hash (too long to index), so the same browser subscribing again updates
 * its row instead of adding a second one.
 *
 * @property int $id
 * @property int $user_id
 * @property string $endpoint
 * @property string $endpoint_hash
 * @property string $public_key
 * @property string $auth_token
 * @property string $content_encoding
 * @property CarbonImmutable|null $expires_at
 * @property string|null $device_id
 * @property string|null $device_name
 * @property string $device_type
 * @property string|null $browser
 * @property string|null $platform
 * @property bool $standalone
 * @property bool $enabled
 * @property bool $urgent_only
 * @property CarbonImmutable|null $last_seen_at
 * @property CarbonImmutable|null $last_success_at
 * @property CarbonImmutable|null $last_failure_at
 * @property int $failure_count
 * @property string|null $last_error
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'endpoint', 'endpoint_hash', 'public_key', 'auth_token', 'content_encoding', 'expires_at',
    'device_id', 'device_name', 'device_type', 'browser', 'platform', 'standalone',
    'enabled', 'urgent_only', 'last_seen_at',
])]
#[Hidden(['endpoint', 'endpoint_hash', 'public_key', 'auth_token'])]
class PushSubscription extends Model
{
    /** @use HasFactory<PushSubscriptionFactory> */
    use HasFactory;

    public const array TYPES = ['desktop', 'mobile', 'tablet'];

    /**
     * Mirrors the column defaults.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'content_encoding' => 'aes128gcm',
        'device_type' => 'desktop',
        'standalone' => false,
        'enabled' => true,
        'urgent_only' => false,
        'failure_count' => 0,
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function hashEndpoint(string $endpoint): string
    {
        return hash('sha256', $endpoint);
    }

    /**
     * Desktop push for computers, mobile push for phones and tablets.
     */
    public function channel(): NotificationChannel
    {
        return $this->device_type === 'desktop' ? NotificationChannel::Desktop : NotificationChannel::Mobile;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Switched on and not expired.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeDeliverable(Builder $query): Builder
    {
        return $query->where('enabled', true)
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'last_success_at' => 'datetime',
            'last_failure_at' => 'datetime',
            'standalone' => 'boolean',
            'enabled' => 'boolean',
            'urgent_only' => 'boolean',
            'failure_count' => 'integer',
        ];
    }
}
