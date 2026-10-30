<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Brings HR's plain Laravel `notifications` table up to tracking's extended
 * inbox schema (plan decision 11), so every module notifies through
 * App\Notifications\AppDatabaseChannel into one table and one bell.
 *
 * Every new column is nullable or has a default: existing HR rows, rows a
 * queued job still writes through Laravel's own shape, and raw inserts keep
 * working. `module`, `subject_*` and `search_text` of existing rows are filled
 * by the next migration. `activity_id` has no foreign key yet: tracking's
 * `activities` table arrives with the tracking port, which adds it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->text('search_text')->nullable()->after('data');
            $table->string('priority', 20)->default('info')->after('search_text');
            $table->string('module', 50)->nullable()->after('priority');
            $table->string('kind', 20)->default('action')->after('module');
            $table->string('subject_type', 50)->nullable()->after('kind');
            $table->unsignedBigInteger('subject_id')->nullable()->after('subject_type');
            $table->foreignId('actor_id')->nullable()->after('subject_id')->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('activity_id')->nullable()->after('actor_id');
            $table->string('group_key', 120)->nullable()->after('activity_id');
            $table->timestamp('snoozed_until')->nullable()->after('group_key');

            $table->index(['notifiable_type', 'notifiable_id', 'read_at'], 'notifications_notifiable_read_index');
            $table->index(['notifiable_id', 'group_key', 'read_at'], 'notifications_group_unread_index');
            $table->index(['notifiable_id', 'subject_type', 'subject_id', 'read_at'], 'notifications_subject_unread_index');
            $table->index('snoozed_until');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_notifiable_read_index');
            $table->dropIndex('notifications_group_unread_index');
            $table->dropIndex('notifications_subject_unread_index');
            $table->dropIndex(['snoozed_until']);
            $table->dropConstrainedForeignId('actor_id');
            $table->dropColumn([
                'search_text', 'priority', 'module', 'kind', 'subject_type', 'subject_id',
                'activity_id', 'group_key', 'snoozed_until',
            ]);
        });
    }
};
