<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\User;
use Modules\Hr\Enums\NotificationCategory;
use Modules\Hr\Enums\NotificationChannel;

/**
 * One person's notification settings. A user without a row gets the column
 * defaults (for()), and the row is only written once they change something.
 *
 * The master switch ($enabled) never touches the channel switches or the
 * category matrix, so turning everything off and on again restores exactly
 * what was set before.
 *
 * @property int $id
 * @property int $user_id
 * @property bool $enabled
 * @property bool $in_app
 * @property bool $desktop
 * @property bool $mobile
 * @property bool $email
 * @property bool $sound
 * @property bool $previews
 * @property string $frequency
 * @property string $digest_time
 * @property bool $quiet_hours
 * @property string $quiet_start
 * @property string $quiet_end
 * @property string|null $timezone
 * @property array<mixed>|null $working_days
 * @property bool $urgent_bypass
 * @property array<mixed>|null $categories
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['enabled', 'in_app', 'desktop', 'mobile', 'email', 'sound', 'previews', 'frequency', 'digest_time', 'quiet_hours', 'quiet_start', 'quiet_end', 'timezone', 'working_days', 'urgent_bypass', 'categories'])]
class NotificationPreference extends Model
{
    public const FREQUENCIES = ['instant', 'hourly', 'daily'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'enabled' => true,
        'in_app' => true,
        'desktop' => true,
        'mobile' => true,
        'email' => true,
        'sound' => true,
        'previews' => true,
        'frequency' => 'instant',
        'digest_time' => '09:00',
        'quiet_hours' => false,
        'quiet_start' => '22:00',
        'quiet_end' => '07:00',
        'urgent_bypass' => true,
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'in_app' => 'boolean',
            'desktop' => 'boolean',
            'mobile' => 'boolean',
            'email' => 'boolean',
            'sound' => 'boolean',
            'previews' => 'boolean',
            'quiet_hours' => 'boolean',
            'urgent_bypass' => 'boolean',
            'working_days' => 'array',
            'categories' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function timezoneName(): string
    {
        return $this->timezone ?: config('app.timezone');
    }

    /**
     * Days push and email may go out, as Carbon dayOfWeek (0 = Sunday).
     *
     * @return array<int, int>
     */
    public function workingDays(): array
    {
        return $this->working_days === null ? range(0, 6) : array_map('intval', $this->working_days);
    }

    /**
     * The global switch for one channel (not the master switch).
     */
    public function channelEnabled(NotificationChannel $channel): bool
    {
        return (bool) $this->{$channel->value};
    }

    /**
     * @return array{in_app: bool, desktop: bool, mobile: bool, email: bool}
     */
    public function categoryChannels(NotificationCategory $category): array
    {
        $saved = $this->categories[$category->value] ?? [];

        return array_map(
            'boolval',
            array_replace($category->defaults(), array_intersect_key($saved, $category->defaults())),
        );
    }

    public function setCategoryChannel(NotificationCategory $category, NotificationChannel $channel, bool $on): void
    {
        $categories = $this->categories ?? [];
        $categories[$category->value] = array_replace($this->categoryChannels($category), [$channel->value => $on]);
        $this->categories = $categories;
    }

    /**
     * The full matrix, every category filled in.
     *
     * @return array<string, array<string, bool>>
     */
    public function matrix(): array
    {
        $matrix = [];

        foreach (NotificationCategory::cases() as $category) {
            $matrix[$category->value] = $this->categoryChannels($category);
        }

        return $matrix;
    }
}
