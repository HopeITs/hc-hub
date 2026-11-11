<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Three tables copied from the HR app that nothing in the hub reads or
     * writes: its web-push subscriptions, its notification outbox and its
     * task-timing telemetry. All three were empty when this was written; the
     * migration stops rather than discard rows if one no longer is.
     */
    public function up(): void
    {
        foreach (['notification_outbox', 'push_subscriptions', 'task_attempts'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $rows = DB::table($table)->count();

            if ($rows > 0) {
                throw new RuntimeException("{$table} holds {$rows} rows; back them up and empty it before dropping it.");
            }
        }

        Schema::dropIfExists('notification_outbox');
        Schema::dropIfExists('push_subscriptions');
        Schema::dropIfExists('task_attempts');
    }

    /**
     * Recreates the three tables as the HR migrations left them, empty.
     */
    public function down(): void
    {
        Schema::create('push_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
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

        Schema::create('notification_outbox', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
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
};
