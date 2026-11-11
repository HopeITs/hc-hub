<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * HR recruitment_setup tables, as they stood in the HR app (hchr_ prefix dropped).
 * Foreign keys are added together in the add_hr_foreign_keys migration so
 * the tables can be created in any order.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hiring_campaigns', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('vacancy_id');
            $table->unsignedTinyInteger('status')->default(0);
            $table->string('link_mode', 60)->default('public_open');
            $table->unsignedTinyInteger('minimum_age')->nullable();
            $table->unsignedTinyInteger('maximum_age')->nullable();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->unsignedBigInteger('created_by_user_id');
            $table->unsignedBigInteger('closed_by_user_id')->nullable();
            $table->timestamps();
            $table->index('closed_by_user_id', 'hc_closer_idx');
            $table->index('created_by_user_id', 'hc_creator_idx');
            $table->index('link_mode', 'hc_link_mode_idx');
            $table->index(['status', 'starts_at', 'ends_at'], 'hc_status_window_idx');
            $table->index(['vacancy_id', 'status'], 'hc_vacancy_status_idx');
        });

        Schema::create('vacancies', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('position_id');
            $table->unsignedBigInteger('created_from_request_id');
            $table->string('status', 60)->default('open');
            $table->unsignedInteger('needed_count');
            $table->unsignedInteger('filled_count')->default(0);
            $table->dateTime('open_at');
            $table->dateTime('closed_at')->nullable();
            $table->text('close_reason')->nullable();
            $table->timestamps();
            $table->index(['position_id', 'status'], 'vac_position_status_idx');
            $table->unique('created_from_request_id', 'vac_request_uq');
            $table->index('status', 'vac_status_idx');
        });

        Schema::create('job_posts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('campaign_id');
            $table->string('status', 60)->default('draft');
            $table->unsignedBigInteger('current_version_id')->nullable();
            $table->unsignedBigInteger('created_by_user_id');
            $table->timestamps();
            $table->unique('campaign_id', 'jp_campaign_uq');
            $table->index('created_by_user_id', 'jp_creator_idx');
            $table->unique('current_version_id', 'jp_current_version_uq');
            $table->index('status', 'jp_status_idx');
        });

        Schema::create('job_post_versions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('job_post_id');
            $table->unsignedInteger('version_no');
            $table->string('title_en');
            $table->string('title_ar');
            $table->text('body_en')->nullable();
            $table->text('body_ar')->nullable();
            $table->json('public_payload_json');
            $table->string('status', 60)->default('draft');
            $table->dateTime('published_at')->nullable();
            $table->timestamps();
            $table->index(['job_post_id', 'status'], 'jpv_post_status_idx');
            $table->unique(['job_post_id', 'version_no'], 'jpv_post_version_uq');
            $table->index('published_at', 'jpv_published_idx');
            $table->index('status', 'jpv_status_idx');
        });

        Schema::create('application_forms', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('campaign_id');
            $table->string('status', 60)->default('draft');
            $table->unsignedBigInteger('current_version_id')->nullable();
            $table->unsignedBigInteger('created_by_user_id');
            $table->timestamps();
            $table->unique('campaign_id', 'af_campaign_uq');
            $table->index('created_by_user_id', 'af_creator_idx');
            $table->unique('current_version_id', 'af_current_version_uq');
            $table->index('status', 'af_status_idx');
        });

        Schema::create('application_form_versions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('application_form_id');
            $table->unsignedInteger('version_no');
            $table->json('fields_json');
            $table->string('privacy_notice_version')->nullable();
            $table->string('status', 60)->default('draft');
            $table->dateTime('published_at')->nullable();
            $table->timestamps();
            $table->index(['application_form_id', 'status'], 'afv_form_status_idx');
            $table->unique(['application_form_id', 'version_no'], 'afv_form_version_uq');
            $table->index('published_at', 'afv_published_idx');
            $table->index('status', 'afv_status_idx');
        });

        Schema::create('public_recruitment_links', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('campaign_id');
            $table->unsignedBigInteger('job_post_version_id');
            $table->unsignedBigInteger('form_version_id');
            $table->string('token_hash', 64);
            $table->text('url_encrypted')->nullable();
            $table->string('status', 60)->default('active');
            $table->dateTime('expires_at');
            $table->unsignedBigInteger('created_by_user_id');
            $table->timestamps();
            $table->index(['campaign_id', 'status'], 'prl_campaign_status_idx');
            $table->index('created_by_user_id', 'prl_creator_idx');
            $table->index('expires_at', 'prl_expires_idx');
            $table->index('form_version_id', 'prl_form_version_idx');
            $table->index('job_post_version_id', 'prl_post_version_idx');
            $table->index(['status', 'expires_at'], 'prl_status_expiry_idx');
            $table->unique('token_hash', 'prl_token_hash_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_recruitment_links');
        Schema::dropIfExists('application_form_versions');
        Schema::dropIfExists('application_forms');
        Schema::dropIfExists('job_post_versions');
        Schema::dropIfExists('job_posts');
        Schema::dropIfExists('vacancies');
        Schema::dropIfExists('hiring_campaigns');
    }
};
