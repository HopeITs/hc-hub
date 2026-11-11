<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * HR news tables, as they stood in the HR app (hchr_ prefix dropped).
 * Foreign keys are added together in the add_hr_foreign_keys migration so
 * the tables can be created in any order.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 20)->default('news');
            $table->string('status', 20)->default('published');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->string('title_ar');
            $table->string('title_en')->nullable();
            $table->text('description_ar');
            $table->text('description_en')->nullable();
            $table->string('newsable_type')->nullable();
            $table->unsignedBigInteger('newsable_id')->nullable();
            $table->string('audience_type', 20)->default('all');
            $table->string('photo')->nullable();
            $table->timestamp('display_until')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->date('event_date')->nullable();
            $table->dateTime('event_starts_at')->nullable();
            $table->dateTime('event_ends_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'published_at'], 'news_status_published_index');
            $table->index(['type', 'event_date'], 'news_type_event_index');
            $table->index(['newsable_type', 'newsable_id'], 'newsable_index');
        });

        Schema::create('news_audience_targets', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('news_id');
            $table->unsignedBigInteger('target_id');
            $table->unique(['news_id', 'target_id']);
            $table->index('target_id');
        });

        Schema::create('news_tags', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('news_id');
            $table->string('tag', 60);
            $table->unique(['news_id', 'tag']);
            $table->index('tag');
        });

        Schema::create('news_external_publications', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('news_id');
            $table->string('platform', 40);
            $table->string('status', 20)->default('pending');
            $table->string('external_id')->nullable();
            $table->string('external_url', 2048)->nullable();
            $table->timestamp('published_at')->nullable();
            $table->text('last_error')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamps();
            $table->unique(['news_id', 'platform']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_external_publications');
        Schema::dropIfExists('news_tags');
        Schema::dropIfExists('news_audience_targets');
        Schema::dropIfExists('news');
    }
};
