<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One browser or installed app that accepted Web Push for a user
 * (App\Models\PushSubscription), with HR's columns. The endpoint is too long
 * to index, so its SHA-256 carries the unique key. HR's own copy was dropped
 * by drop_unused_hr_tables; this one is the hub's.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('endpoint');
            $table->char('endpoint_hash', 64)->unique();
            $table->string('public_key', 255);
            $table->string('auth_token', 255);
            $table->string('content_encoding', 16)->default('aes128gcm');
            $table->timestamp('expires_at')->nullable();
            $table->string('device_id', 36)->nullable()->index();
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
            $table->string('last_error', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
    }
};
