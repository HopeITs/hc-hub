<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Push and email copies of notifications still to send, and the earliest
 * moment each may go (App\Models\NotificationOutboxEntry), with HR's columns.
 * category holds the notification module. The unique (notification_id,
 * channel) pair makes writing the same copy twice a no-op; group_key (the
 * notification's) lets a burst on one record push or mail only once.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_outbox', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->uuid('notification_id')->nullable();
            $table->string('channel', 16);
            $table->string('category', 32);
            $table->string('group_key', 191)->nullable();
            $table->boolean('urgent')->default(false);
            $table->json('payload');
            $table->timestamp('deliver_after')->index();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->string('error', 255)->nullable();
            $table->timestamps();

            $table->unique(['notification_id', 'channel']);
            $table->index(['sent_at', 'deliver_after']);
            $table->index(['user_id', 'channel', 'group_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_outbox');
    }
};
