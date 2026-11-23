<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NotificationChannel;
use Carbon\CarbonImmutable;
use Database\Factories\NotificationPreferenceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\User;

/**
 * One person's delivery settings (ported from HR). A person without a row
 * gets the defaults (for()); the row is only written once they change
 * something.
 *
 * `categories` is keyed by notification module (config('notifications.modules')
 * plus `system`), each holding {in_app, desktop, mobile, email}. A module
 * nobody touched falls back to MODULE_DEFAULTS: everything on except email.
 *
 * The master switch ($enabled) never touches the channel switches or the
 * module matrix, so turning everything off and on again restores exactly
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
 * @property list<int>|null $working_days
 * @property bool $urgent_bypass
 * @property array<string, array<string, bool>>|null $categories
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'enabled', 'in_app', 'desktop', 'mobile', 'email', 'sound', 'previews',
    'frequency', 'digest_time', 'quiet_hours', 'quiet_start', 'quiet_end',
    'timezone', 'working_days', 'urgent_bypass', 'categories',
])]
class NotificationPreference extends Model
{
    /** @use HasFactory<NotificationPreferenceFactory> */
    use HasFactory;

    public const array FREQUENCIES = ['instant', 'hourly', 'daily'];

    /**
     * The category for notifications outside every module (tests, sign-in
     * alerts the platform raises itself).
     */
    public const string SYSTEM = 'system';

    /**
     * What a module gets until the person changes it: every channel except
     * email, which they opt into per module.
     *
     * @var array{in_app: bool, desktop: bool, mobile: bool, email: bool}
     */
    public const array MODULE_DEFAULTS = ['in_app' => true, 'desktop' => true, 'mobile' => true, 'email' => false];

    /**
     * Mirrors the column defaults, so an unsaved row answers like a saved one.
     *
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

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The saved row, or an unsaved one holding the defaults.
     */
    public static function for(User $user): self
    {
        return $user->notificationPreference()->firstOrNew();
    }

    public function timezoneName(): string
    {
        return $this->timezone ?: (string) config('app.timezone');
    }

    /**
     * Days push and email may go out, as Carbon dayOfWeek (0 = Sunday).
     *
     * @return list<int>
     */
    public function workingDays(): array
    {
        return $this->working_days === null ? range(0, 6) : array_values(array_map('intval', $this->working_days));
    }

    /**
     * The global switch for one channel (not the master switch).
     */
    public function channelEnabled(NotificationChannel $channel): bool
    {
        return (bool) $this->{$channel->value};
    }

    /**
     * One module's cells: the defaults overlaid with what was saved.
     *
     * @return array{in_app: bool, desktop: bool, mobile: bool, email: bool}
     */
    public function categoryChannels(string $module): array
    {
        $saved = $this->categories[$module] ?? [];

        return array_map(
            boolval(...),
            array_replace(self::MODULE_DEFAULTS, array_intersect_key((array) $saved, self::MODULE_DEFAULTS)),
        );
    }

    public function setCategoryChannel(string $module, NotificationChannel $channel, bool $on): void
    {
        $categories = $this->categories ?? [];
        $categories[$module] = array_replace($this->categoryChannels($module), [$channel->value => $on]);
        $this->categories = $categories;
    }

    /**
     * The full matrix for the given modules (App\Notifications\Delivery\ReceivableModules).
     *
     * @param  list<string>  $modules
     * @return array<string, array{in_app: bool, desktop: bool, mobile: bool, email: bool}>
     */
    public function matrix(array $modules): array
    {
        $matrix = [];

        foreach ($modules as $module) {
            $matrix[$module] = $this->categoryChannels($module);
        }

        return $matrix;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
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
}
