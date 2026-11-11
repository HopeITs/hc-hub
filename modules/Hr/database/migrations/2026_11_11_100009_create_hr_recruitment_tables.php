<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * HR recruitment tables, as they stood in the HR app (hchr_ prefix dropped).
 * Foreign keys are added together in the add_hr_foreign_keys migration so
 * the tables can be created in any order.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment_applicant_files', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('applicant_id');
            $table->unsignedBigInteger('application_id')->nullable();
            $table->string('file_type', 60);
            $table->string('scan_status', 60)->default('pending');
            $table->dateTime('uploaded_at');
            $table->dateTime('archived_at')->nullable();
            $table->unsignedBigInteger('archived_by_user_id')->nullable();
            $table->unsignedBigInteger('archive_note_id')->nullable();
            $table->timestamps();
            $table->dateTime('purged_at')->nullable();
            $table->index(['applicant_id', 'file_type'], 'raf_applicant_type_idx');
            $table->index('application_id', 'raf_application_idx');
            $table->index('archived_at', 'raf_archived_idx');
            $table->index('scan_status', 'raf_scan_status_idx');
        });

        Schema::create('recruitment_applicant_notes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('applicant_id');
            $table->unsignedBigInteger('application_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->string('note_type');
            $table->boolean('is_sensitive')->default(false);
            $table->text('body');
            $table->timestamp('created_at')->useCurrent();
            $table->softDeletes();
            $table->unsignedBigInteger('deleted_by_user_id')->nullable();
            $table->text('deletion_reason')->nullable();
            $table->index(['applicant_id', 'created_at'], 'ran_applicant_created_idx');
            $table->index(['application_id', 'created_at'], 'ran_application_created_idx');
            $table->index('deleted_at', 'ran_deleted_idx');
            $table->index('user_id', 'ran_user_idx');
        });

        Schema::create('recruitment_applicants', function (Blueprint $table): void {
            $table->id();
            $table->string('full_name', 191);
            $table->string('email_normalized', 191);
            $table->string('phone_normalized', 50)->nullable();
            $table->string('phone_match_key', 20)->nullable();
            $table->string('identity_key_hash', 64)->nullable();
            $table->unsignedBigInteger('possible_duplicate_of_applicant_id')->nullable();
            $table->dateTime('identity_verified_at')->nullable();
            $table->unsignedBigInteger('city_id')->nullable();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->string('source', 60);
            $table->dateTime('last_seen_at')->nullable();
            $table->dateTime('retention_until')->nullable();
            $table->dateTime('anonymized_at')->nullable();
            $table->timestamps();
            $table->string('privacy_notice_version')->nullable();
            $table->dateTime('privacy_accepted_at')->nullable();
            $table->dateTime('erasure_requested_at')->nullable();
            $table->string('anonymization_reason', 40)->nullable();
            $table->index('city_id', 'ra_city_idx');
            $table->index('email_normalized', 'ra_email_idx');
            $table->index('employee_id', 'ra_employee_idx');
            $table->index('erasure_requested_at', 'ra_erasure_idx');
            $table->unique('identity_key_hash', 'ra_identity_hash_uq');
            $table->index('phone_normalized', 'ra_phone_idx');
            $table->index('phone_match_key', 'ra_phone_match_idx');
            $table->index('possible_duplicate_of_applicant_id', 'ra_possible_dup_idx');
            $table->index('retention_until', 'ra_retention_idx');
        });

        Schema::create('recruitment_application_answers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('application_id');
            $table->unsignedBigInteger('form_version_id');
            $table->string('field_key');
            $table->json('answer_json');
            $table->timestamps();
            $table->unique(['application_id', 'field_key'], 'raa_application_field_uq');
            $table->index('form_version_id', 'raa_form_version_idx');
        });

        Schema::create('recruitment_application_decisions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('application_id');
            $table->string('from_status', 60);
            $table->string('to_status', 60);
            $table->string('reason_code', 60);
            $table->text('reason_text')->nullable();
            $table->unsignedBigInteger('decided_by_user_id');
            $table->dateTime('decided_at');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['application_id', 'decided_at'], 'rad_application_decided_idx');
            $table->index('decided_by_user_id', 'rad_decider_idx');
            $table->index('from_status', 'rad_from_status_idx');
            $table->index('to_status', 'rad_to_status_idx');
        });

        Schema::create('recruitment_application_endorsements', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('application_id');
            $table->unsignedBigInteger('user_id');
            $table->string('voter_role', 20);
            $table->timestamp('created_at')->useCurrent();
            $table->index(['application_id', 'voter_role'], 'raen_application_role_idx');
            $table->unique(['application_id', 'user_id'], 'raen_application_user_uq');
            $table->index('user_id', 'raen_user_idx');
        });

        Schema::create('recruitment_application_flags', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('application_id');
            $table->string('type', 20);
            $table->string('category', 40);
            $table->string('reason', 80);
            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by_user_id');
            $table->timestamps();
            $table->index(['application_id', 'type']);
            $table->index(['category', 'reason']);
        });

        Schema::create('recruitment_application_indicator_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('application_id');
            $table->string('criteria_version', 64);
            $table->unsignedTinyInteger('met_count');
            $table->unsignedTinyInteger('assessed_count');
            $table->json('indicators_json');
            $table->dateTime('computed_at');
            $table->unsignedBigInteger('computed_by_user_id')->nullable();
            $table->timestamps();
            $table->unique('application_id', 'rais_application_uq');
            $table->index('criteria_version', 'rais_criteria_idx');
        });

        Schema::create('recruitment_application_participants', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('application_id');
            $table->unsignedBigInteger('applicant_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->string('role', 20);
            $table->unsignedBigInteger('added_by_user_id');
            $table->timestamp('removed_at')->nullable();
            $table->unsignedBigInteger('removed_by_user_id')->nullable();
            $table->timestamps();
            $table->index(['applicant_id', 'user_id'], 'ra_participants_applicant_user_idx');
            $table->unique(['application_id', 'user_id'], 'ra_participants_application_user_unique');
            $table->index(['user_id', 'removed_at'], 'ra_participants_user_removed_idx');
        });

        Schema::create('recruitment_application_reviews', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('application_id');
            $table->unsignedBigInteger('reviewer_user_id');
            $table->string('recommendation', 30);
            $table->text('pros')->nullable();
            $table->text('cons')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamp('submitted_at');
            $table->timestamps();
            $table->unique(['application_id', 'reviewer_user_id'], 'ra_reviews_application_reviewer_unique');
        });

        Schema::create('recruitment_application_tags', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('application_id');
            $table->unsignedBigInteger('tag_id');
            $table->unsignedBigInteger('created_by_user_id');
            $table->timestamps();
            $table->unique(['application_id', 'tag_id'], 'rat_application_tag_uq');
            $table->index('created_by_user_id', 'rat_creator_idx');
            $table->index(['tag_id', 'application_id'], 'rat_tag_application_idx');
            $table->index('tag_id', 'rat_tag_idx');
        });

        Schema::create('recruitment_applications', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('applicant_id');
            $table->unsignedBigInteger('campaign_id');
            $table->unsignedBigInteger('vacancy_id');
            $table->unsignedBigInteger('form_version_id');
            $table->unsignedBigInteger('public_link_id');
            $table->unsignedBigInteger('campaign_invite_id')->nullable();
            $table->string('submission_key_hash', 64)->nullable();
            $table->string('email_normalized', 191);
            $table->string('status', 60)->default('submitted');
            $table->string('privacy_notice_version')->nullable();
            $table->dateTime('privacy_accepted_at')->nullable();
            $table->json('consent_payload_json')->nullable();
            $table->dateTime('submitted_at');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            $table->unique(['applicant_id', 'campaign_id'], 'rapp_applicant_campaign_uq');
            $table->unique(['campaign_id', 'email_normalized'], 'rapp_campaign_email_uq');
            $table->index(['campaign_id', 'status'], 'rapp_campaign_status_idx');
            $table->index('email_normalized', 'rapp_email_idx');
            $table->index('form_version_id', 'rapp_form_version_idx');
            $table->unique('campaign_invite_id', 'rapp_invite_uq');
            $table->index('public_link_id', 'rapp_public_link_idx');
            $table->index('status', 'rapp_status_idx');
            $table->unique('submission_key_hash', 'rapp_submission_hash_uq');
            $table->index('submitted_at', 'rapp_submitted_idx');
            $table->index(['vacancy_id', 'status'], 'rapp_vacancy_status_idx');
        });

        Schema::create('recruitment_campaign_invites', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('campaign_id');
            $table->string('full_name', 191);
            $table->string('email_normalized', 191);
            $table->string('status', 60)->default('pending');
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('failed_at')->nullable();
            $table->string('failure_reason', 60)->nullable();
            $table->dateTime('used_at')->nullable();
            $table->unsignedBigInteger('created_by_user_id');
            $table->timestamps();
            $table->unique(['campaign_id', 'email_normalized'], 'rci_campaign_email_uq');
            $table->index(['campaign_id', 'status'], 'rci_campaign_status_idx');
            $table->index('created_by_user_id', 'rci_creator_idx');
            $table->index('email_normalized', 'rci_email_idx');
            $table->index('status', 'rci_status_idx');
        });

        Schema::create('recruitment_cv_analyses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('file_id');
            $table->unsignedBigInteger('application_id')->nullable();
            $table->string('status', 30);
            $table->string('extraction_method', 30)->nullable();
            $table->unsignedInteger('text_chars')->default(0);
            $table->json('facts_json')->nullable();
            $table->json('comparison_json')->nullable();
            $table->string('analyzer_version', 20);
            $table->string('error', 191)->nullable();
            $table->dateTime('analyzed_at');
            $table->timestamps();
            $table->index('application_id', 'rca_application_idx');
            $table->unique('file_id', 'rca_file_uq');
            $table->index('status', 'rca_status_idx');
        });

        Schema::create('recruitment_hiring_conversions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('job_offer_id');
            $table->unsignedBigInteger('job_offer_version_id');
            $table->unsignedBigInteger('applicant_id');
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->unsignedBigInteger('contract_id')->nullable();
            $table->json('employee_payload_json');
            $table->string('status', 60)->default('pending');
            $table->text('failure_reason')->nullable();
            $table->unsignedSmallInteger('attempt_count')->default(0);
            $table->dateTime('started_at');
            $table->unsignedBigInteger('converted_by_user_id');
            $table->dateTime('converted_at')->nullable();
            $table->timestamps();
            $table->index('applicant_id', 'rhc_applicant_idx');
            $table->unique('contract_id', 'rhc_contract_uq');
            $table->index('converted_by_user_id', 'rhc_converter_idx');
            $table->index('employee_id', 'rhc_employee_idx');
            $table->unique('job_offer_id', 'rhc_job_offer_uq');
            $table->index('job_offer_version_id', 'rhc_offer_version_idx');
            $table->index(['status', 'started_at'], 'rhc_status_started_idx');
        });

        Schema::create('recruitment_indicator_criteria_versions', function (Blueprint $table): void {
            $table->id();
            $table->string('version', 64);
            $table->json('definition_json');
            $table->timestamp('created_at')->useCurrent();
            $table->unique('version', 'ricv_version_uq');
        });

        Schema::create('recruitment_indicator_settings', function (Blueprint $table): void {
            $table->id();
            $table->json('overrides_json');
            $table->unsignedBigInteger('updated_by_user_id')->nullable();
            $table->timestamps();
        });

        Schema::create('recruitment_interview_evaluation_notes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('evaluation_id');
            $table->unsignedBigInteger('author_user_id');
            $table->text('body');
            $table->timestamps();
            $table->index(['evaluation_id', 'created_at'], 'ri_eval_notes_evaluation_created_idx');
        });

        Schema::create('recruitment_interview_evaluation_revisions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('evaluation_id');
            $table->unsignedInteger('version');
            $table->string('action', 20);
            $table->text('pros')->nullable();
            $table->text('cons')->nullable();
            $table->text('notes')->nullable();
            $table->string('recommendation');
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('changed_by_user_id');
            $table->dateTime('changed_at');
            $table->unique(['evaluation_id', 'version'], 'rier_evaluation_version_uq');
        });

        Schema::create('recruitment_interview_evaluations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('interview_id');
            $table->unsignedBigInteger('reviewer_user_id');
            $table->text('pros')->nullable();
            $table->text('cons')->nullable();
            $table->text('notes')->nullable();
            $table->string('recommendation');
            $table->unsignedInteger('version')->default(1);
            $table->dateTime('submitted_at');
            $table->unsignedBigInteger('updated_by_user_id')->nullable();
            $table->timestamps();
            $table->dateTime('withdrawn_at')->nullable();
            $table->unsignedBigInteger('withdrawn_by_user_id')->nullable();
            $table->text('withdrawal_reason')->nullable();
            $table->unique(['interview_id', 'reviewer_user_id'], 'rie_interview_reviewer_uq');
            $table->index('reviewer_user_id', 'rie_reviewer_idx');
            $table->index('submitted_at', 'rie_submitted_idx');
        });

        Schema::create('recruitment_interview_notices', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('interview_id');
            $table->string('kind', 20);
            $table->dateTime('scheduled_for');
            $table->unsignedInteger('offset_minutes')->nullable();
            $table->string('recipient_email', 191);
            $table->string('status', 20);
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->unsignedBigInteger('requested_by_user_id')->nullable();
            $table->dateTime('queued_at');
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('failed_at')->nullable();
            $table->string('failure_reason', 191)->nullable();
            $table->timestamps();
            $table->unique(['interview_id', 'kind', 'scheduled_for', 'offset_minutes'], 'rin_reminder_slot_uq');
            $table->index(['status', 'queued_at'], 'rin_status_queued_idx');
        });

        Schema::create('recruitment_interview_participants', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('interview_id');
            $table->unsignedBigInteger('user_id');
            $table->string('role', 60);
            $table->string('notification_status', 60)->default('pending');
            $table->unsignedBigInteger('added_by_user_id')->nullable();
            $table->dateTime('removed_at')->nullable();
            $table->unsignedBigInteger('removed_by_user_id')->nullable();
            $table->timestamps();
            $table->unique(['interview_id', 'user_id'], 'rip_interview_user_uq');
            $table->index(['user_id', 'notification_status'], 'rip_user_notification_idx');
        });

        Schema::create('recruitment_interview_required_participants', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('interview_id');
            $table->string('seat', 20);
            $table->unsignedBigInteger('owner_user_id');
            $table->unsignedBigInteger('attendee_user_id');
            $table->timestamps();
            $table->index('attendee_user_id', 'rirp_attendee_idx');
            $table->unique(['interview_id', 'seat'], 'rirp_interview_seat_uq');
        });

        Schema::create('recruitment_interview_reschedules', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('interview_id');
            $table->dateTime('from_scheduled_at');
            $table->dateTime('from_ends_at')->nullable();
            $table->dateTime('to_scheduled_at');
            $table->dateTime('to_ends_at')->nullable();
            $table->string('from_location_type', 60);
            $table->string('to_location_type', 60);
            $table->boolean('location_changed')->default(false);
            $table->boolean('reminder_already_sent')->default(false);
            $table->text('reason');
            $table->unsignedBigInteger('rescheduled_by_user_id');
            $table->dateTime('rescheduled_at');
            $table->index(['interview_id', 'rescheduled_at'], 'rir_interview_at_idx');
        });

        Schema::create('recruitment_interviews', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('application_id');
            $table->string('interview_type', 60);
            $table->string('status', 60)->default('scheduled');
            $table->dateTime('scheduled_at');
            $table->dateTime('ends_at')->nullable();
            $table->string('timezone', 64)->nullable();
            $table->string('location_type', 60);
            $table->string('location_value', 2048)->nullable();
            $table->text('meeting_url')->nullable();
            $table->dateTime('slot_set_at')->nullable();
            $table->unsignedSmallInteger('reschedule_count')->default(0);
            $table->unsignedBigInteger('created_by_user_id');
            $table->timestamps();
            $table->dateTime('cancelled_at')->nullable();
            $table->unsignedBigInteger('cancelled_by_user_id')->nullable();
            $table->string('cancellation_reason', 60)->nullable();
            $table->text('cancellation_note')->nullable();
            $table->dateTime('outcome_recorded_at')->nullable();
            $table->unsignedBigInteger('outcome_recorded_by_user_id')->nullable();
            $table->text('outcome_note')->nullable();
            $table->unsignedBigInteger('updated_by_user_id')->nullable();
            $table->dateTime('evaluations_closed_at')->nullable();
            $table->unsignedBigInteger('evaluations_closed_by_user_id')->nullable();
            $table->index(['application_id', 'scheduled_at'], 'ri_application_scheduled_idx');
            $table->index('created_by_user_id', 'ri_creator_idx');
            $table->index('scheduled_at', 'ri_scheduled_idx');
            $table->index(['status', 'scheduled_at'], 'ri_status_scheduled_idx');
        });

        Schema::create('recruitment_job_offer_versions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('job_offer_id');
            $table->unsignedInteger('version_no');
            $table->unsignedBigInteger('contract_type_id')->nullable();
            $table->text('salary_amount')->nullable();
            $table->unsignedTinyInteger('currency')->nullable();
            $table->unsignedTinyInteger('salary_per')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('weekly_hours')->nullable();
            $table->unsignedInteger('probation_days')->nullable();
            $table->json('terms_json')->nullable();
            $table->text('revision_reason')->nullable();
            $table->string('status', 60)->default('draft');
            $table->unsignedBigInteger('created_by_user_id');
            $table->unsignedBigInteger('updated_by_user_id')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->timestamps();
            $table->index('contract_type_id', 'rjov_contract_type_idx');
            $table->index('created_by_user_id', 'rjov_creator_idx');
            $table->index(['job_offer_id', 'status'], 'rjov_offer_status_idx');
            $table->unique(['job_offer_id', 'version_no'], 'rjov_offer_version_uq');
            $table->index('status', 'rjov_status_idx');
        });

        Schema::create('recruitment_job_offers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('application_id');
            $table->unsignedBigInteger('vacancy_id');
            $table->unsignedBigInteger('position_id');
            $table->string('status', 60)->default('draft');
            $table->unsignedBigInteger('current_version_id')->nullable();
            $table->unsignedBigInteger('created_by_user_id');
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('accepted_at')->nullable();
            $table->dateTime('rejected_at')->nullable();
            $table->unsignedBigInteger('response_recorded_by_user_id')->nullable();
            $table->text('response_note')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('expired_at')->nullable();
            $table->timestamps();
            $table->dateTime('cancelled_at')->nullable();
            $table->unsignedBigInteger('cancelled_by_user_id')->nullable();
            $table->string('cancellation_reason', 60)->nullable();
            $table->text('cancellation_note')->nullable();
            $table->unsignedBigInteger('updated_by_user_id')->nullable();
            $table->unique('application_id', 'rjo_application_uq');
            $table->index('created_by_user_id', 'rjo_creator_idx');
            $table->unique('current_version_id', 'rjo_current_version_uq');
            $table->index('expires_at', 'rjo_expires_idx');
            $table->index('position_id', 'rjo_position_idx');
            $table->index('status', 'rjo_status_idx');
            $table->index(['vacancy_id', 'status'], 'rjo_vacancy_status_idx');
        });

        Schema::create('recruitment_privacy_audits', function (Blueprint $table): void {
            $table->id();
            $table->string('action', 40);
            $table->string('outcome', 20);
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->string('actor_role', 60)->nullable();
            $table->string('scope_type', 20);
            $table->json('scope_city_ids')->nullable();
            $table->json('filters_json')->nullable();
            $table->unsignedInteger('record_count')->default(0);
            $table->string('subject_type', 60)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('reason', 191)->nullable();
            $table->json('details_json')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['action', 'created_at'], 'rpa_action_created_idx');
            $table->index(['actor_user_id', 'created_at'], 'rpa_actor_created_idx');
            $table->index(['subject_type', 'subject_id'], 'rpa_subject_idx');
        });

        Schema::create('recruitment_rejection_notices', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('application_id');
            $table->unsignedBigInteger('decision_id')->nullable();
            $table->string('recipient_email', 191);
            $table->string('status', 20);
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->unsignedBigInteger('requested_by_user_id');
            $table->dateTime('queued_at');
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('failed_at')->nullable();
            $table->string('failure_reason', 191)->nullable();
            $table->timestamps();
            $table->unique('application_id', 'rrn_application_uq');
            $table->index('status', 'rrn_status_idx');
        });

        Schema::create('recruitment_request_comments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('request_id');
            $table->unsignedBigInteger('request_version_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->unsignedTinyInteger('comment_type')->default(1);
            $table->text('body');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['request_id', 'created_at'], 'rrc_request_created_idx');
            $table->index('user_id', 'rrc_user_idx');
            $table->index(['request_version_id', 'created_at'], 'rrc_version_created_idx');
        });

        Schema::create('recruitment_request_events', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('recruitment_request_id');
            $table->unsignedBigInteger('request_version_id')->nullable();
            $table->unsignedTinyInteger('event_type');
            $table->unsignedBigInteger('workflow_instance_id')->nullable();
            $table->unsignedBigInteger('workflow_step_id')->nullable();
            $table->unsignedTinyInteger('from_status')->nullable();
            $table->unsignedTinyInteger('to_status')->nullable();
            $table->unsignedTinyInteger('decision')->nullable();
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->string('actor_role', 60);
            $table->unsignedTinyInteger('scope_type')->nullable();
            $table->unsignedBigInteger('scope_id')->nullable();
            $table->text('comment')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index('actor_user_id', 'rre_actor_idx');
            $table->index('decision', 'rre_decision_idx');
            $table->index('event_type', 'rre_event_type_idx');
            $table->index('from_status', 'rre_from_status_idx');
            $table->index(['recruitment_request_id', 'created_at'], 'rre_request_created_idx');
            $table->index('request_version_id', 'rre_request_version_idx');
            $table->index('scope_id', 'rre_scope_id_idx');
            $table->index(['scope_type', 'scope_id'], 'rre_scope_idx');
            $table->index('to_status', 'rre_to_status_idx');
            $table->index('workflow_instance_id', 'rre_workflow_instance_idx');
            $table->index('workflow_step_id', 'rre_workflow_step_idx');
        });

        Schema::create('recruitment_request_external_confirmations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('recruitment_request_id');
            $table->unsignedTinyInteger('step_key')->nullable();
            $table->boolean('is_generated')->default(false);
            $table->string('confirmer_name', 191)->nullable();
            $table->unsignedBigInteger('confirmer_user_id')->nullable();
            $table->unsignedBigInteger('confirmer_position_id')->nullable();
            $table->unsignedTinyInteger('decision')->nullable();
            $table->unsignedTinyInteger('method')->nullable();
            $table->dateTime('confirmed_at')->nullable();
            $table->text('not_applicable_reason')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('recorded_by_user_id');
            $table->timestamps();
            $table->index('confirmer_position_id', 'rrec_confirmer_position_idx');
            $table->index('confirmer_user_id', 'rrec_confirmer_user_idx');
            $table->index('decision', 'rrec_decision_idx');
            $table->index('recorded_by_user_id', 'rrec_recorder_idx');
            $table->index(['recruitment_request_id', 'created_at'], 'rrec_request_created_idx');
            $table->unique(['recruitment_request_id', 'step_key'], 'rrec_request_step_uq');
        });

        Schema::create('recruitment_request_position_details', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('request_version_id');
            $table->unsignedBigInteger('position_id')->nullable();
            $table->unsignedBigInteger('program_id')->nullable();
            $table->unsignedBigInteger('center_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('job_level_id')->nullable();
            $table->unsignedBigInteger('contract_type_id')->nullable();
            $table->unsignedBigInteger('direct_supervisor_position_id')->nullable();
            $table->integer('current_headcount')->nullable();
            $table->integer('requested_headcount')->nullable();
            $table->text('recruitment_reason')->nullable();
            $table->text('other_reason')->nullable();
            $table->string('role_title_en')->nullable();
            $table->string('role_title_ar')->nullable();
            $table->date('volunteer_period_start_date')->nullable();
            $table->date('volunteer_period_end_date')->nullable();
            $table->date('expected_start_date')->nullable();
            $table->integer('weekly_hours')->nullable();
            $table->string('priority', 60)->nullable();
            $table->string('volunteer_type', 60)->nullable();
            $table->integer('salary_amount')->nullable();
            $table->unsignedTinyInteger('currency')->nullable();
            $table->unsignedTinyInteger('salary_per')->nullable();
            $table->text('main_duties')->nullable();
            $table->text('required_qualifications')->nullable();
            $table->integer('required_experience_years')->nullable();
            $table->text('required_skills')->nullable();
            $table->text('detailed_explanation')->nullable();
            $table->boolean('role_description_ready')->default(false);
            $table->boolean('finance_consulted')->default(false);
            $table->boolean('org_chart_updated')->default(false);
            $table->text('reason')->nullable();
            $table->timestamps();
            $table->index('center_id', 'rrpd_center_idx');
            $table->index('contract_type_id', 'rrpd_contract_type_idx');
            $table->index('department_id', 'rrpd_department_idx');
            $table->index('job_level_id', 'rrpd_job_level_idx');
            $table->index('position_id', 'rrpd_position_idx');
            $table->index('program_id', 'rrpd_program_idx');
            $table->unique('request_version_id', 'rrpd_request_version_uq');
            $table->index('direct_supervisor_position_id', 'rrpd_supervisor_idx');
        });

        Schema::create('recruitment_request_versions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('request_id');
            $table->unsignedInteger('version_no');
            $table->json('snapshot_json');
            $table->unsignedBigInteger('created_by_user_id');
            $table->dateTime('submitted_at');
            $table->timestamps();
            $table->index('created_by_user_id', 'rrv_creator_idx');
            $table->unique(['request_id', 'version_no'], 'rrv_request_version_uq');
            $table->index('submitted_at', 'rrv_submitted_idx');
        });

        Schema::create('recruitment_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('request_number', 50);
            $table->unsignedBigInteger('requester_user_id');
            $table->unsignedTinyInteger('request_type');
            $table->unsignedTinyInteger('mode')->default(1);
            $table->unsignedTinyInteger('status')->default(0);
            $table->unsignedBigInteger('scope_city_id')->nullable();
            $table->unsignedBigInteger('target_position_id')->nullable();
            $table->unsignedBigInteger('target_vacancy_id')->nullable();
            $table->json('draft_payload_json')->nullable();
            $table->string('actual_requester_name', 191)->nullable();
            $table->unsignedBigInteger('actual_requester_user_id')->nullable();
            $table->unsignedBigInteger('actual_requester_position_id')->nullable();
            $table->text('direct_open_reason')->nullable();
            $table->unsignedBigInteger('direct_opened_by_user_id')->nullable();
            $table->dateTime('direct_opened_at')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('applied_at')->nullable();
            $table->timestamps();
            $table->index('actual_requester_position_id', 'rr_actual_position_idx');
            $table->index('actual_requester_user_id', 'rr_actual_requester_user_idx');
            $table->index('direct_opened_by_user_id', 'rr_direct_opener_idx');
            $table->index(['target_position_id', 'status'], 'rr_position_status_idx');
            $table->unique('request_number', 'rr_request_number_uq');
            $table->index('request_type', 'rr_request_type_idx');
            $table->index(['requester_user_id', 'status'], 'rr_requester_status_idx');
            $table->index(['scope_city_id', 'status'], 'rr_scope_status_idx');
            $table->index('status', 'rr_status_idx');
            $table->index('submitted_at', 'rr_submitted_idx');
            $table->index(['target_vacancy_id', 'status'], 'rr_vacancy_status_idx');
        });

        Schema::create('recruitment_tags', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 191);
            $table->string('color', 20)->nullable();
            $table->string('kind', 20)->default('neutral');
            $table->string('scope_key');
            $table->unsignedBigInteger('created_by_user_id');
            $table->unsignedBigInteger('updated_by_user_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('deleted_by_user_id')->nullable();
            $table->index('kind');
            $table->index('created_by_user_id', 'rt_creator_idx');
            $table->index('deleted_at', 'rt_deleted_idx');
            $table->unique(['scope_key', 'name'], 'rt_scope_name_uq');
        });

        Schema::create('recruitment_workflow_definition_versions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('definition_id');
            $table->unsignedInteger('version_no');
            $table->json('rules_json');
            $table->boolean('is_active')->default(false);
            $table->dateTime('published_at')->nullable();
            $table->timestamps();
            $table->index(['definition_id', 'is_active'], 'rwdv_definition_active_idx');
            $table->unique(['definition_id', 'version_no'], 'rwdv_definition_version_uq');
            $table->index('published_at', 'rwdv_published_idx');
        });

        Schema::create('recruitment_workflow_definitions', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 191);
            $table->unsignedTinyInteger('request_type');
            $table->boolean('is_active')->default(false);
            $table->unsignedBigInteger('created_by_user_id');
            $table->timestamps();
            $table->index('created_by_user_id', 'rwd_creator_idx');
            $table->index(['request_type', 'is_active'], 'rwd_type_active_idx');
        });

        Schema::create('recruitment_workflow_instance_steps', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('workflow_instance_id');
            $table->unsignedBigInteger('workflow_step_id');
            $table->string('assigned_role', 60);
            $table->unsignedTinyInteger('assigned_scope_type')->nullable();
            $table->unsignedBigInteger('assigned_scope_id')->nullable();
            $table->unsignedBigInteger('assigned_user_id')->nullable();
            $table->unsignedBigInteger('acted_by_user_id')->nullable();
            $table->unsignedTinyInteger('decision')->nullable();
            $table->text('decision_reason')->nullable();
            $table->dateTime('acted_at')->nullable();
            $table->timestamps();
            $table->index('acted_by_user_id', 'rwis_actor_idx');
            $table->index(['assigned_user_id', 'decision'], 'rwis_assignee_decision_idx');
            $table->index('decision', 'rwis_decision_idx');
            $table->unique(['workflow_instance_id', 'workflow_step_id'], 'rwis_instance_step_uq');
            $table->index('assigned_scope_id', 'rwis_scope_id_idx');
            $table->index(['assigned_scope_type', 'assigned_scope_id'], 'rwis_scope_idx');
            $table->index('workflow_step_id', 'rwis_workflow_step_idx');
        });

        Schema::create('recruitment_workflow_instances', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('request_id');
            $table->unsignedBigInteger('request_version_id');
            $table->unsignedBigInteger('definition_version_id');
            $table->unsignedTinyInteger('status')->default(0);
            $table->unsignedInteger('current_step_order')->default(1);
            $table->dateTime('started_at');
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
            $table->index('definition_version_id', 'rwi_definition_version_idx');
            $table->index(['request_id', 'status'], 'rwi_request_status_idx');
            $table->unique('request_version_id', 'rwi_request_version_uq');
            $table->index('status', 'rwi_status_idx');
        });

        Schema::create('recruitment_workflow_steps', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('definition_version_id');
            $table->unsignedInteger('step_order');
            $table->unsignedTinyInteger('step_key');
            $table->string('role_key', 60);
            $table->unsignedTinyInteger('scope_rule');
            $table->json('required_condition_json')->nullable();
            $table->timestamps();
            $table->unique(['definition_version_id', 'step_key'], 'rws_version_key_uq');
            $table->unique(['definition_version_id', 'step_order'], 'rws_version_order_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_workflow_steps');
        Schema::dropIfExists('recruitment_workflow_instances');
        Schema::dropIfExists('recruitment_workflow_instance_steps');
        Schema::dropIfExists('recruitment_workflow_definitions');
        Schema::dropIfExists('recruitment_workflow_definition_versions');
        Schema::dropIfExists('recruitment_tags');
        Schema::dropIfExists('recruitment_requests');
        Schema::dropIfExists('recruitment_request_versions');
        Schema::dropIfExists('recruitment_request_position_details');
        Schema::dropIfExists('recruitment_request_external_confirmations');
        Schema::dropIfExists('recruitment_request_events');
        Schema::dropIfExists('recruitment_request_comments');
        Schema::dropIfExists('recruitment_rejection_notices');
        Schema::dropIfExists('recruitment_privacy_audits');
        Schema::dropIfExists('recruitment_job_offers');
        Schema::dropIfExists('recruitment_job_offer_versions');
        Schema::dropIfExists('recruitment_interviews');
        Schema::dropIfExists('recruitment_interview_reschedules');
        Schema::dropIfExists('recruitment_interview_required_participants');
        Schema::dropIfExists('recruitment_interview_participants');
        Schema::dropIfExists('recruitment_interview_notices');
        Schema::dropIfExists('recruitment_interview_evaluations');
        Schema::dropIfExists('recruitment_interview_evaluation_revisions');
        Schema::dropIfExists('recruitment_interview_evaluation_notes');
        Schema::dropIfExists('recruitment_indicator_settings');
        Schema::dropIfExists('recruitment_indicator_criteria_versions');
        Schema::dropIfExists('recruitment_hiring_conversions');
        Schema::dropIfExists('recruitment_cv_analyses');
        Schema::dropIfExists('recruitment_campaign_invites');
        Schema::dropIfExists('recruitment_applications');
        Schema::dropIfExists('recruitment_application_tags');
        Schema::dropIfExists('recruitment_application_reviews');
        Schema::dropIfExists('recruitment_application_participants');
        Schema::dropIfExists('recruitment_application_indicator_snapshots');
        Schema::dropIfExists('recruitment_application_flags');
        Schema::dropIfExists('recruitment_application_endorsements');
        Schema::dropIfExists('recruitment_application_decisions');
        Schema::dropIfExists('recruitment_application_answers');
        Schema::dropIfExists('recruitment_applicants');
        Schema::dropIfExists('recruitment_applicant_notes');
        Schema::dropIfExists('recruitment_applicant_files');
    }
};
