<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * HR personal tables, as they stood in the HR app (hchr_ prefix dropped).
 * Foreign keys are added together in the add_hr_foreign_keys migration so
 * the tables can be created in any order.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_layouts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('role')->comment('The active role name as stored (HR-ALP, HR-SYR, manager...), not collapsed');
            $table->json('layout')->nullable()->comment('[{key, size: CxR, pinned, hidden, style?}] in display order');
            $table->timestamps();
            $table->unique(['user_id', 'role']);
        });

        Schema::create('navigation_preferences', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->json('favourites')->nullable()->comment('Menu item ids (Navigation), in the order shown, at most 8');
            $table->json('recent_pages')->nullable()->comment('Menu item ids, most recent first');
            $table->json('recent_people')->nullable()->comment('employees.id, most recent first');
            $table->json('bottom_bar')->nullable()->comment('Phone bar shortcuts (App\\Support\\BottomBar keys), in order; null = the default bar');
            $table->timestamps();
            $table->unique('user_id');
        });

        Schema::create('saved_table_views', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('table', 100);
            $table->string('role_context', 64);
            $table->string('name', 60);
            $table->json('state');
            $table->timestamps();
            $table->unique(['user_id', 'table', 'role_context', 'name'], 'saved_table_views_unique_name');
        });

        Schema::create('assistant_conversations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('title', 120);
            $table->longText('messages')->nullable()->comment('encrypted JSON');
            $table->timestamps();
            $table->index(['user_id', 'updated_at']);
        });

        Schema::create('in_app_feedback', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('category', 16)->nullable();
            $table->text('message');
            $table->string('route_name', 150)->nullable();
            $table->string('locale', 5);
            $table->string('role_name', 64)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index('created_at');
        });

        Schema::create('push_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->text('endpoint');
            $table->char('endpoint_hash', 64);
            $table->string('public_key');
            $table->string('auth_token');
            $table->string('content_encoding', 16)->default('aes128gcm');
            $table->timestamp('expires_at')->nullable();
            $table->string('device_id', 36)->nullable();
            $table->string('device_name', 80)->nullable();
            $table->string('device_type', 16)->default('desktop');
            $table->string('browser', 40)->nullable();
            $table->string('platform', 40)->nullable();
            $table->boolean('standalone')->default(false);
            $table->boolean('enabled')->default(true);
            $table->boolean('urgent_only')->default(false);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('last_success_at')->nullable();
            $table->timestamp('last_failure_at')->nullable();
            $table->unsignedSmallInteger('failure_count')->default(0);
            $table->string('last_error')->nullable();
            $table->timestamps();
            $table->index('device_id');
            $table->unique('endpoint_hash');
        });

        Schema::create('notification_preferences', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->boolean('enabled')->default(true);
            $table->boolean('in_app')->default(true);
            $table->boolean('desktop')->default(true);
            $table->boolean('mobile')->default(true);
            $table->boolean('email')->default(false);
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
            $table->unique('user_id');
        });

        Schema::create('notification_outbox', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->char('notification_id', 36)->nullable();
            $table->string('channel', 16);
            $table->string('category', 32);
            $table->boolean('urgent')->default(false);
            $table->json('payload');
            $table->timestamp('deliver_after');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->string('error')->nullable();
            $table->timestamps();
            $table->index('deliver_after');
            $table->unique(['notification_id', 'channel']);
            $table->index(['sent_at', 'deliver_after']);
        });

        Schema::create('notification_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->string('idempotency_key', 191);
            $table->string('kind', 64);
            $table->unsignedBigInteger('recipient_id');
            $table->unsignedBigInteger('subject_id');
            $table->date('local_date');
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
            $table->unique('idempotency_key');
            $table->index(['kind', 'local_date']);
        });

        Schema::create('task_attempts', function (Blueprint $table): void {
            $table->id();
            $table->char('attempt_uuid', 36);
            $table->string('task', 32);
            $table->string('role_context', 20);
            $table->string('locale', 5);
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('outcome', 16)->nullable();
            $table->boolean('had_validation_error')->default(false);
            $table->unique('attempt_uuid');
            $table->index('started_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_attempts');
        Schema::dropIfExists('notification_deliveries');
        Schema::dropIfExists('notification_outbox');
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('push_subscriptions');
        Schema::dropIfExists('in_app_feedback');
        Schema::dropIfExists('assistant_conversations');
        Schema::dropIfExists('saved_table_views');
        Schema::dropIfExists('navigation_preferences');
        Schema::dropIfExists('dashboard_layouts');
    }
};
