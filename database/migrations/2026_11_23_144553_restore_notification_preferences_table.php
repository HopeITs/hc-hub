<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Brings HR's notification_preferences back into use as the hub's per-person
 * delivery settings (App\Models\NotificationPreference). The HR import parked
 * it as zz_drop_notification_preferences (retire_unused_hr_preference_tables);
 * when that parked table exists it is renamed back so people keep their
 * switches, quiet hours and working days. Otherwise it is created.
 *
 * The categories JSON is cleared: HR keyed it by HR categories (leave_actions,
 * contracts...), the hub keys it by notification module, so the old cells
 * would mean nothing. Everyone starts from the module defaults.
 */
return new class extends Migration
{
    private const string PARKED = 'zz_drop_notification_preferences';

    public function up(): void
    {
        if (! Schema::hasTable('notification_preferences')) {
            if (Schema::hasTable(self::PARKED)) {
                Schema::rename(self::PARKED, 'notification_preferences');
            } else {
                $this->create();
            }
        }

        DB::table('notification_preferences')->update(['categories' => null]);
    }

    /**
     * Parks the table again (rows kept), so the HR migration's own down()
     * finds it where it left it. The cleared categories do not come back.
     */
    public function down(): void
    {
        if (Schema::hasTable('notification_preferences') && ! Schema::hasTable(self::PARKED)) {
            Schema::rename('notification_preferences', self::PARKED);
        }
    }

    private function create(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->boolean('enabled')->default(true);
            $table->boolean('in_app')->default(true);
            $table->boolean('desktop')->default(true);
            $table->boolean('mobile')->default(true);
            $table->boolean('email')->default(true);
            $table->boolean('sound')->default(true);
            $table->boolean('previews')->default(true);
            $table->string('frequency', 16)->default('instant');
            $table->string('digest_time', 5)->default('09:00');
            $table->boolean('quiet_hours')->default(false);
            $table->string('quiet_start', 5)->default('22:00');
            $table->string('quiet_end', 5)->default('07:00');
            $table->string('timezone', 64)->nullable();
            $table->json('working_days')->nullable();
            $table->boolean('urgent_bypass')->default(true);
            $table->json('categories')->nullable();
            $table->timestamps();
        });
    }
};
