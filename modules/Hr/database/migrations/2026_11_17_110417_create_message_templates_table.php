<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * HR's email and WhatsApp texts with {placeholders}. Nothing is sent from
 * them: a template is filled in for one person and opens as a new message
 * (MessageTemplateRenderer).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            // 'email' (opens a new message) or 'whatsapp' (opens a chat).
            $table->string('channel', 16);
            // The language the text is written in: 'en' or 'ar'.
            $table->string('language', 2)->default('en');
            $table->string('subject', 255)->nullable();
            $table->text('body');
            // Email only: copy the person's direct manager.
            $table->boolean('cc_manager')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['is_active', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_templates');
    }
};
