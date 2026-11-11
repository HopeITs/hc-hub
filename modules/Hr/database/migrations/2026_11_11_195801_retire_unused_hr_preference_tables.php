<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Five tables copied from the HR app that nothing in the hub reads: the
     * hub keeps a person's layout, navigation and notification choices on
     * users and in notification_mutes / notification_settings, and logs AI
     * use in ai_requests. They still hold a few rows, so they are parked
     * under a zz_drop_ name instead of dropped; the rows, indexes and foreign
     * keys stay as they are until the final drop is confirmed.
     *
     * @var array<string, string>
     */
    private const array PARKED = [
        'assistant_conversations' => 'zz_drop_assistant_conversations',
        'dashboard_layouts' => 'zz_drop_dashboard_layouts',
        'navigation_preferences' => 'zz_drop_navigation_preferences',
        'notification_preferences' => 'zz_drop_notification_preferences',
        'saved_table_views' => 'zz_drop_saved_table_views',
    ];

    public function up(): void
    {
        foreach (self::PARKED as $table => $parked) {
            if (Schema::hasTable($table) && ! Schema::hasTable($parked)) {
                Schema::rename($table, $parked);
            }
        }
    }

    public function down(): void
    {
        foreach (self::PARKED as $table => $parked) {
            if (Schema::hasTable($parked) && ! Schema::hasTable($table)) {
                Schema::rename($parked, $table);
            }
        }
    }
};
