<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every foreign key of the HR tables, between themselves and to the Core
 * tables (users, programs, cities, centers).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('positions', function (Blueprint $table): void {
            $table->foreign('center_id', 'positions_center_id_foreign')->references('id')->on('centers')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('city_id', 'positions_city_id_foreign')->references('id')->on('cities')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('department_id', 'positions_department_id_foreign')->references('id')->on('departments')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('direct_manager_id', 'positions_direct_manager_id_foreign')->references('id')->on('positions')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('job_level_id', 'positions_job_level_id_foreign')->references('id')->on('job_levels')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('program_id', 'positions_program_id_foreign')->references('id')->on('programs')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('tech_manager_id', 'positions_tech_manager_id_foreign')->references('id')->on('positions')->nullOnDelete()->cascadeOnUpdate();
        });

        Schema::table('employees', function (Blueprint $table): void {
            $table->foreign('city_id', 'employees_city_id_foreign')->references('id')->on('cities')->nullOnDelete();
            $table->foreign('last_saved_by', 'employees_last_saved_by_foreign')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('educations', function (Blueprint $table): void {
            $table->foreign('employee_id', 'educations_employee_id_foreign')->references('id')->on('employees')->cascadeOnDelete()->cascadeOnUpdate();
        });

        Schema::table('experiences', function (Blueprint $table): void {
            $table->foreign('employee_id', 'experiences_employee_id_foreign')->references('id')->on('employees')->cascadeOnDelete()->cascadeOnUpdate();
        });

        Schema::table('courses', function (Blueprint $table): void {
            $table->foreign('category_id', 'courses_category_id_foreign')->references('id')->on('course_categories')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('employee_id', 'courses_employee_id_foreign')->references('id')->on('employees')->cascadeOnDelete()->cascadeOnUpdate();
        });

        Schema::table('relatives', function (Blueprint $table): void {
            $table->foreign('employee_id', 'relatives_employee_id_foreign')->references('id')->on('employees')->cascadeOnDelete();
        });

        Schema::table('employee_items', function (Blueprint $table): void {
            $table->foreign('employee_id', 'employee_items_employee_id_foreign')->references('id')->on('employees')->cascadeOnDelete()->cascadeOnUpdate();
        });

        Schema::table('employee_notes', function (Blueprint $table): void {
            $table->foreign('employee_id', 'employee_notes_employee_id_foreign')->references('id')->on('employees')->cascadeOnDelete()->cascadeOnUpdate();
        });

        Schema::table('evaluations', function (Blueprint $table): void {
            $table->foreign('contract_id', 'evaluations_contract_id_foreign')->references('id')->on('contracts')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('current_manager_id', 'evaluations_current_manager_id_foreign')->references('id')->on('employees')->nullOnDelete()->cascadeOnUpdate();
        });

        Schema::table('badges', function (Blueprint $table): void {
            $table->foreign('employee_id', 'badges_employee_id_foreign')->references('id')->on('employees')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('evaluation_id', 'badges_evaluation_id_foreign')->references('id')->on('evaluations')->restrictOnDelete()->cascadeOnUpdate();
        });

        Schema::table('contracts', function (Blueprint $table): void {
            $table->foreign('contract_type_id', 'contracts_contract_type_id_foreign')->references('id')->on('contract_types')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('employee_id', 'contracts_employee_id_foreign')->references('id')->on('employees')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('position_id', 'contracts_position_id_foreign')->references('id')->on('positions')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('termination_reason_id', 'contracts_termination_reason_id_foreign')->references('id')->on('termination_reasons')->restrictOnDelete()->cascadeOnUpdate();
        });

        Schema::table('contract_pay_tiers', function (Blueprint $table): void {
            $table->foreign('contract_id', 'contract_pay_tiers_contract_id_foreign')->references('id')->on('contracts')->cascadeOnDelete();
        });

        Schema::table('contract_amendments', function (Blueprint $table): void {
            $table->foreign('approved_by', 'contract_amendments_approved_by_foreign')->references('id')->on('users')->nullOnDelete();
            $table->foreign('contract_id', 'contract_amendments_contract_id_foreign')->references('id')->on('contracts')->cascadeOnDelete();
            $table->foreign('created_by', 'contract_amendments_created_by_foreign')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('contract_amendment_pay_tiers', function (Blueprint $table): void {
            $table->foreign('contract_amendment_id', 'contract_amendment_pay_tiers_contract_amendment_id_foreign')->references('id')->on('contract_amendments')->cascadeOnDelete();
        });

        Schema::table('default_deliverables', function (Blueprint $table): void {
            $table->foreign('city_id', 'default_deliverables_city_id_foreign')->references('id')->on('cities')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign('employee_id', 'default_deliverables_employee_id_foreign')->references('id')->on('employees')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('item_id', 'default_deliverables_item_id_foreign')->references('id')->on('checklist_items')->restrictOnDelete()->cascadeOnUpdate();
        });

        Schema::table('leave_balances', function (Blueprint $table): void {
            $table->foreign('contract_id', 'leave_balances_contract_id_foreign')->references('id')->on('contracts')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('type_id', 'leave_balances_type_id_foreign')->references('id')->on('leave_types')->restrictOnDelete()->cascadeOnUpdate();
        });

        Schema::table('leave_requests', function (Blueprint $table): void {
            $table->foreign('alternative_employee_id', 'leave_requests_alternative_employee_id_foreign')->references('id')->on('employees')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('approved_by', 'leave_requests_approved_by_foreign')->references('id')->on('employees')->nullOnDelete()->cascadeOnUpdate();
            $table->foreign('leave_balance_id', 'leave_requests_leave_balance_id_foreign')->references('id')->on('leave_balances')->restrictOnDelete()->cascadeOnUpdate();
        });

        Schema::table('leave_request_notes', function (Blueprint $table): void {
            $table->foreign('leave_request_id', 'leave_request_notes_leave_request_id_foreign')->references('id')->on('leave_requests')->cascadeOnDelete();
        });

        Schema::table('holidays', function (Blueprint $table): void {
            $table->foreign('created_by', 'holidays_created_by_foreign')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('holiday_project', function (Blueprint $table): void {
            $table->foreign('city_id', 'holiday_project_city_id_foreign')->references('id')->on('cities')->cascadeOnDelete();
            $table->foreign('holiday_id', 'holiday_project_holiday_id_foreign')->references('id')->on('holidays')->cascadeOnDelete();
            $table->foreign('program_id', 'holiday_project_program_id_foreign')->references('id')->on('programs')->cascadeOnDelete();
        });

        Schema::table('birthday_holiday_choices', function (Blueprint $table): void {
            $table->foreign('decided_by', 'birthday_holiday_choices_decided_by_foreign')->references('id')->on('users')->nullOnDelete();
            $table->foreign('employee_id', 'birthday_holiday_choices_employee_id_foreign')->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('holiday_id', 'birthday_holiday_choices_holiday_id_foreign')->references('id')->on('holidays')->cascadeOnDelete();
        });

        Schema::table('onboarding_journeys', function (Blueprint $table): void {
            $table->foreign('card_handed_over_by', 'onboarding_journeys_card_handed_over_by_foreign')->references('id')->on('users')->nullOnDelete();
            $table->foreign('contract_id', 'onboarding_journeys_contract_id_foreign')->references('id')->on('contracts')->nullOnDelete();
            $table->foreign('employee_id', 'onboarding_journeys_employee_id_foreign')->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('orientation_completed_by', 'onboarding_journeys_orientation_completed_by_foreign')->references('id')->on('users')->nullOnDelete();
            $table->foreign('orientation_scheduled_by', 'onboarding_journeys_orientation_scheduled_by_foreign')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('offboarding_journeys', function (Blueprint $table): void {
            $table->foreign('contract_id', 'offboarding_journeys_contract_id_foreign')->references('id')->on('contracts')->nullOnDelete();
            $table->foreign('employee_id', 'offboarding_journeys_employee_id_foreign')->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('exit_interview_by', 'offboarding_journeys_exit_interview_by_foreign')->references('id')->on('users')->nullOnDelete();
            $table->foreign('started_by', 'offboarding_journeys_started_by_foreign')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('renewal_journeys', function (Blueprint $table): void {
            $table->foreign('contract_id', 'renewal_journeys_contract_id_foreign')->references('id')->on('contracts')->cascadeOnDelete();
            $table->foreign('decided_by', 'renewal_journeys_decided_by_foreign')->references('id')->on('users')->nullOnDelete();
            $table->foreign('employee_id', 'renewal_journeys_employee_id_foreign')->references('id')->on('employees')->cascadeOnDelete();
        });

        Schema::table('profile_change_requests', function (Blueprint $table): void {
            $table->foreign('decided_by', 'profile_change_requests_decided_by_foreign')->references('id')->on('users')->nullOnDelete();
            $table->foreign('employee_id', 'profile_change_requests_employee_id_foreign')->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('requested_by', 'profile_change_requests_requested_by_foreign')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('checklists', function (Blueprint $table): void {
            $table->foreign('delivered_id', 'checklists_delivered_id_foreign')->references('id')->on('employees')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('employee_id', 'checklists_employee_id_foreign')->references('id')->on('employees')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('receiver_id', 'checklists_receiver_id_foreign')->references('id')->on('employees')->cascadeOnDelete();
        });

        Schema::table('folders', function (Blueprint $table): void {
            $table->foreign('parent_id', 'folders_parent_id_foreign')->references('id')->on('folders')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('user_id', 'folders_user_id_foreign')->references('id')->on('users')->nullOnDelete()->cascadeOnUpdate();
        });

        Schema::table('folder_user', function (Blueprint $table): void {
            $table->foreign('folder_id', 'folder_user_folder_id_foreign')->references('id')->on('folders')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('user_id', 'folder_user_user_id_foreign')->references('id')->on('users')->cascadeOnDelete()->cascadeOnUpdate();
        });

        Schema::table('news', function (Blueprint $table): void {
            $table->foreign('created_by', 'news_created_by_foreign')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'news_updated_by_foreign')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('news_audience_targets', function (Blueprint $table): void {
            $table->foreign('news_id', 'news_audience_targets_news_id_foreign')->references('id')->on('news')->cascadeOnDelete();
        });

        Schema::table('news_tags', function (Blueprint $table): void {
            $table->foreign('news_id', 'news_tags_news_id_foreign')->references('id')->on('news')->cascadeOnDelete();
        });

        Schema::table('news_external_publications', function (Blueprint $table): void {
            $table->foreign('news_id', 'news_external_publications_news_id_foreign')->references('id')->on('news')->cascadeOnDelete();
        });

        Schema::table('dashboard_layouts', function (Blueprint $table): void {
            $table->foreign('user_id', 'dashboard_layouts_user_id_foreign')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('navigation_preferences', function (Blueprint $table): void {
            $table->foreign('user_id', 'navigation_preferences_user_id_foreign')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('saved_table_views', function (Blueprint $table): void {
            $table->foreign('user_id', 'saved_table_views_user_id_foreign')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('assistant_conversations', function (Blueprint $table): void {
            $table->foreign('user_id', 'assistant_conversations_user_id_foreign')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('in_app_feedback', function (Blueprint $table): void {
            $table->foreign('user_id', 'in_app_feedback_user_id_foreign')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('push_subscriptions', function (Blueprint $table): void {
            $table->foreign('user_id', 'push_subscriptions_user_id_foreign')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('notification_preferences', function (Blueprint $table): void {
            $table->foreign('user_id', 'notification_preferences_user_id_foreign')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('notification_outbox', function (Blueprint $table): void {
            $table->foreign('user_id', 'notification_outbox_user_id_foreign')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('notification_deliveries', function (Blueprint $table): void {
            $table->foreign('recipient_id', 'notification_deliveries_recipient_id_foreign')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('hiring_campaigns', function (Blueprint $table): void {
            $table->foreign('closed_by_user_id', 'hc_closer_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by_user_id', 'hc_creator_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('vacancy_id', 'hc_vacancy_fk')->references('id')->on('vacancies')->restrictOnDelete();
        });

        Schema::table('vacancies', function (Blueprint $table): void {
            $table->foreign('position_id', 'vac_position_fk')->references('id')->on('positions')->restrictOnDelete();
            $table->foreign('created_from_request_id', 'vac_request_fk')->references('id')->on('recruitment_requests')->restrictOnDelete();
        });

        Schema::table('job_posts', function (Blueprint $table): void {
            $table->foreign('current_version_id', 'job_posts_current_version_id_foreign')->references('id')->on('job_post_versions')->nullOnDelete();
            $table->foreign('campaign_id', 'jp_campaign_fk')->references('id')->on('hiring_campaigns')->cascadeOnDelete();
            $table->foreign('created_by_user_id', 'jp_creator_fk')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::table('job_post_versions', function (Blueprint $table): void {
            $table->foreign('job_post_id', 'jpv_job_post_fk')->references('id')->on('job_posts')->cascadeOnDelete();
        });

        Schema::table('application_forms', function (Blueprint $table): void {
            $table->foreign('campaign_id', 'af_campaign_fk')->references('id')->on('hiring_campaigns')->cascadeOnDelete();
            $table->foreign('created_by_user_id', 'af_creator_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('current_version_id', 'application_forms_current_version_id_foreign')->references('id')->on('application_form_versions')->nullOnDelete();
        });

        Schema::table('application_form_versions', function (Blueprint $table): void {
            $table->foreign('application_form_id', 'afv_form_fk')->references('id')->on('application_forms')->cascadeOnDelete();
        });

        Schema::table('public_recruitment_links', function (Blueprint $table): void {
            $table->foreign('campaign_id', 'prl_campaign_fk')->references('id')->on('hiring_campaigns')->cascadeOnDelete();
            $table->foreign('created_by_user_id', 'prl_creator_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('form_version_id', 'prl_form_version_fk')->references('id')->on('application_form_versions')->restrictOnDelete();
            $table->foreign('job_post_version_id', 'prl_post_version_fk')->references('id')->on('job_post_versions')->restrictOnDelete();
        });

        Schema::table('recruitment_applicant_files', function (Blueprint $table): void {
            $table->foreign('applicant_id', 'raf_applicant_fk')->references('id')->on('recruitment_applicants')->restrictOnDelete();
            $table->foreign('application_id', 'raf_application_fk')->references('id')->on('recruitment_applications')->nullOnDelete();
            $table->foreign('archive_note_id', 'raf_archive_note_fk')->references('id')->on('recruitment_applicant_notes')->nullOnDelete();
            $table->foreign('archived_by_user_id', 'raf_archived_by_fk')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::table('recruitment_applicant_notes', function (Blueprint $table): void {
            $table->foreign('applicant_id', 'ran_applicant_fk')->references('id')->on('recruitment_applicants')->cascadeOnDelete();
            $table->foreign('application_id', 'ran_application_fk')->references('id')->on('recruitment_applications')->nullOnDelete();
            $table->foreign('deleted_by_user_id', 'ran_deleted_by_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('user_id', 'ran_user_fk')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::table('recruitment_applicants', function (Blueprint $table): void {
            $table->foreign('city_id', 'ra_city_fk')->references('id')->on('cities')->nullOnDelete();
            $table->foreign('employee_id', 'ra_employee_fk')->references('id')->on('employees')->nullOnDelete();
            $table->foreign('possible_duplicate_of_applicant_id', 'ra_possible_dup_fk')->references('id')->on('recruitment_applicants')->nullOnDelete();
        });

        Schema::table('recruitment_application_answers', function (Blueprint $table): void {
            $table->foreign('application_id', 'raa_application_fk')->references('id')->on('recruitment_applications')->cascadeOnDelete();
            $table->foreign('form_version_id', 'raa_form_version_fk')->references('id')->on('application_form_versions')->restrictOnDelete();
        });

        Schema::table('recruitment_application_decisions', function (Blueprint $table): void {
            $table->foreign('application_id', 'rad_application_fk')->references('id')->on('recruitment_applications')->cascadeOnDelete();
            $table->foreign('decided_by_user_id', 'rad_decider_fk')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::table('recruitment_application_endorsements', function (Blueprint $table): void {
            $table->foreign('application_id', 'raen_application_fk')->references('id')->on('recruitment_applications')->cascadeOnDelete();
            $table->foreign('user_id', 'raen_user_fk')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::table('recruitment_application_flags', function (Blueprint $table): void {
            $table->foreign('application_id', 'recruitment_application_flags_application_id_foreign')->references('id')->on('recruitment_applications')->cascadeOnDelete();
            $table->foreign('created_by_user_id', 'recruitment_application_flags_created_by_user_id_foreign')->references('id')->on('users')->noActionOnDelete();
        });

        Schema::table('recruitment_application_indicator_snapshots', function (Blueprint $table): void {
            $table->foreign('application_id', 'rais_application_fk')->references('id')->on('recruitment_applications')->cascadeOnDelete();
            $table->foreign('computed_by_user_id', 'rais_computed_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('criteria_version', 'rais_criteria_fk')->references('version')->on('recruitment_indicator_criteria_versions')->restrictOnDelete();
        });

        Schema::table('recruitment_application_participants', function (Blueprint $table): void {
            $table->foreign('added_by_user_id', 'ra_participants_added_by_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('applicant_id', 'ra_participants_applicant_fk')->references('id')->on('recruitment_applicants')->cascadeOnDelete();
            $table->foreign('application_id', 'ra_participants_application_fk')->references('id')->on('recruitment_applications')->cascadeOnDelete();
            $table->foreign('removed_by_user_id', 'ra_participants_removed_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('user_id', 'ra_participants_user_fk')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('recruitment_application_reviews', function (Blueprint $table): void {
            $table->foreign('application_id', 'ra_reviews_application_fk')->references('id')->on('recruitment_applications')->cascadeOnDelete();
            $table->foreign('reviewer_user_id', 'ra_reviews_reviewer_fk')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::table('recruitment_application_tags', function (Blueprint $table): void {
            $table->foreign('application_id', 'rat_application_fk')->references('id')->on('recruitment_applications')->cascadeOnDelete();
            $table->foreign('created_by_user_id', 'rat_creator_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('tag_id', 'rat_tag_fk')->references('id')->on('recruitment_tags')->cascadeOnDelete();
        });

        Schema::table('recruitment_applications', function (Blueprint $table): void {
            $table->foreign('applicant_id', 'rapp_applicant_fk')->references('id')->on('recruitment_applicants')->restrictOnDelete();
            $table->foreign('campaign_id', 'rapp_campaign_fk')->references('id')->on('hiring_campaigns')->restrictOnDelete();
            $table->foreign('form_version_id', 'rapp_form_version_fk')->references('id')->on('application_form_versions')->restrictOnDelete();
            $table->foreign('campaign_invite_id', 'rapp_invite_fk')->references('id')->on('recruitment_campaign_invites')->restrictOnDelete();
            $table->foreign('public_link_id', 'rapp_public_link_fk')->references('id')->on('public_recruitment_links')->restrictOnDelete();
            $table->foreign('vacancy_id', 'rapp_vacancy_fk')->references('id')->on('vacancies')->restrictOnDelete();
        });

        Schema::table('recruitment_campaign_invites', function (Blueprint $table): void {
            $table->foreign('campaign_id', 'rci_campaign_fk')->references('id')->on('hiring_campaigns')->cascadeOnDelete();
            $table->foreign('created_by_user_id', 'rci_creator_fk')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::table('recruitment_cv_analyses', function (Blueprint $table): void {
            $table->foreign('application_id', 'rca_application_fk')->references('id')->on('recruitment_applications')->cascadeOnDelete();
            $table->foreign('file_id', 'rca_file_fk')->references('id')->on('recruitment_applicant_files')->cascadeOnDelete();
        });

        Schema::table('recruitment_hiring_conversions', function (Blueprint $table): void {
            $table->foreign('applicant_id', 'rhc_applicant_fk')->references('id')->on('recruitment_applicants')->restrictOnDelete();
            $table->foreign('contract_id', 'rhc_contract_fk')->references('id')->on('contracts')->restrictOnDelete();
            $table->foreign('converted_by_user_id', 'rhc_converter_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('employee_id', 'rhc_employee_fk')->references('id')->on('employees')->restrictOnDelete();
            $table->foreign('job_offer_id', 'rhc_job_offer_fk')->references('id')->on('recruitment_job_offers')->restrictOnDelete();
            $table->foreign('job_offer_version_id', 'rhc_offer_version_fk')->references('id')->on('recruitment_job_offer_versions')->restrictOnDelete();
        });

        Schema::table('recruitment_indicator_settings', function (Blueprint $table): void {
            $table->foreign('updated_by_user_id', 'recruitment_indicator_settings_updated_by_user_id_foreign')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('recruitment_interview_evaluation_notes', function (Blueprint $table): void {
            $table->foreign('author_user_id', 'ri_eval_notes_author_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('evaluation_id', 'ri_eval_notes_evaluation_fk')->references('id')->on('recruitment_interview_evaluations')->restrictOnDelete();
        });

        Schema::table('recruitment_interview_evaluation_revisions', function (Blueprint $table): void {
            $table->foreign('changed_by_user_id', 'rier_actor_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('evaluation_id', 'rier_evaluation_fk')->references('id')->on('recruitment_interview_evaluations')->restrictOnDelete();
        });

        Schema::table('recruitment_interview_evaluations', function (Blueprint $table): void {
            $table->foreign('interview_id', 'rie_interview_fk')->references('id')->on('recruitment_interviews')->cascadeOnDelete();
            $table->foreign('reviewer_user_id', 'rie_reviewer_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('updated_by_user_id', 'rie_updater_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('withdrawn_by_user_id', 'rie_withdrawer_fk')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::table('recruitment_interview_notices', function (Blueprint $table): void {
            $table->foreign('interview_id', 'rin_interview_fk')->references('id')->on('recruitment_interviews')->restrictOnDelete();
            $table->foreign('requested_by_user_id', 'rin_requester_fk')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::table('recruitment_interview_participants', function (Blueprint $table): void {
            $table->foreign('added_by_user_id', 'rip_adder_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('interview_id', 'rip_interview_fk')->references('id')->on('recruitment_interviews')->cascadeOnDelete();
            $table->foreign('removed_by_user_id', 'rip_remover_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('user_id', 'rip_user_fk')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::table('recruitment_interview_required_participants', function (Blueprint $table): void {
            $table->foreign('attendee_user_id', 'rirp_attendee_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('interview_id', 'rirp_interview_fk')->references('id')->on('recruitment_interviews')->cascadeOnDelete();
            $table->foreign('owner_user_id', 'rirp_owner_fk')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::table('recruitment_interview_reschedules', function (Blueprint $table): void {
            $table->foreign('rescheduled_by_user_id', 'rir_actor_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('interview_id', 'rir_interview_fk')->references('id')->on('recruitment_interviews')->restrictOnDelete();
        });

        Schema::table('recruitment_interviews', function (Blueprint $table): void {
            $table->foreign('application_id', 'ri_application_fk')->references('id')->on('recruitment_applications')->restrictOnDelete();
            $table->foreign('cancelled_by_user_id', 'ri_canceller_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('created_by_user_id', 'ri_creator_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('evaluations_closed_by_user_id', 'ri_eval_closer_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('outcome_recorded_by_user_id', 'ri_outcome_recorder_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('updated_by_user_id', 'ri_updater_fk')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::table('recruitment_job_offer_versions', function (Blueprint $table): void {
            $table->foreign('contract_type_id', 'rjov_contract_type_fk')->references('id')->on('contract_types')->restrictOnDelete();
            $table->foreign('created_by_user_id', 'rjov_creator_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('job_offer_id', 'rjov_job_offer_fk')->references('id')->on('recruitment_job_offers')->cascadeOnDelete();
            $table->foreign('updated_by_user_id', 'rjov_updater_fk')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::table('recruitment_job_offers', function (Blueprint $table): void {
            $table->foreign('current_version_id', 'recruitment_job_offers_current_version_id_foreign')->references('id')->on('recruitment_job_offer_versions')->nullOnDelete();
            $table->foreign('application_id', 'rjo_application_fk')->references('id')->on('recruitment_applications')->restrictOnDelete();
            $table->foreign('cancelled_by_user_id', 'rjo_canceller_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('created_by_user_id', 'rjo_creator_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('position_id', 'rjo_position_fk')->references('id')->on('positions')->restrictOnDelete();
            $table->foreign('response_recorded_by_user_id', 'rjo_responder_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('updated_by_user_id', 'rjo_updater_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('vacancy_id', 'rjo_vacancy_fk')->references('id')->on('vacancies')->restrictOnDelete();
        });

        Schema::table('recruitment_privacy_audits', function (Blueprint $table): void {
            $table->foreign('actor_user_id', 'rpa_actor_fk')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::table('recruitment_rejection_notices', function (Blueprint $table): void {
            $table->foreign('application_id', 'rrn_application_fk')->references('id')->on('recruitment_applications')->cascadeOnDelete();
            $table->foreign('decision_id', 'rrn_decision_fk')->references('id')->on('recruitment_application_decisions')->nullOnDelete();
            $table->foreign('requested_by_user_id', 'rrn_requester_fk')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::table('recruitment_request_comments', function (Blueprint $table): void {
            $table->foreign('request_id', 'rrc_request_fk')->references('id')->on('recruitment_requests')->restrictOnDelete();
            $table->foreign('user_id', 'rrc_user_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('request_version_id', 'rrc_version_fk')->references('id')->on('recruitment_request_versions')->nullOnDelete();
        });

        Schema::table('recruitment_request_events', function (Blueprint $table): void {
            $table->foreign('actor_user_id', 'rre_actor_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('recruitment_request_id', 'rre_request_fk')->references('id')->on('recruitment_requests')->restrictOnDelete();
            $table->foreign('request_version_id', 'rre_request_version_fk')->references('id')->on('recruitment_request_versions')->nullOnDelete();
            $table->foreign('workflow_instance_id', 'rre_workflow_instance_fk')->references('id')->on('recruitment_workflow_instances')->nullOnDelete();
            $table->foreign('workflow_step_id', 'rre_workflow_step_fk')->references('id')->on('recruitment_workflow_steps')->nullOnDelete();
        });

        Schema::table('recruitment_request_external_confirmations', function (Blueprint $table): void {
            $table->foreign('confirmer_position_id', 'rrec_confirmer_position_fk')->references('id')->on('positions')->nullOnDelete();
            $table->foreign('confirmer_user_id', 'rrec_confirmer_user_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('recorded_by_user_id', 'rrec_recorder_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('recruitment_request_id', 'rrec_request_fk')->references('id')->on('recruitment_requests')->restrictOnDelete();
        });

        Schema::table('recruitment_request_position_details', function (Blueprint $table): void {
            $table->foreign('center_id', 'rrpd_center_fk')->references('id')->on('centers')->restrictOnDelete();
            $table->foreign('contract_type_id', 'rrpd_contract_type_fk')->references('id')->on('contract_types')->restrictOnDelete();
            $table->foreign('department_id', 'rrpd_department_fk')->references('id')->on('departments')->restrictOnDelete();
            $table->foreign('job_level_id', 'rrpd_job_level_fk')->references('id')->on('job_levels')->restrictOnDelete();
            $table->foreign('position_id', 'rrpd_position_fk')->references('id')->on('positions')->restrictOnDelete();
            $table->foreign('program_id', 'rrpd_program_fk')->references('id')->on('programs')->restrictOnDelete();
            $table->foreign('request_version_id', 'rrpd_request_version_fk')->references('id')->on('recruitment_request_versions')->cascadeOnDelete();
            $table->foreign('direct_supervisor_position_id', 'rrpd_supervisor_fk')->references('id')->on('positions')->restrictOnDelete();
        });

        Schema::table('recruitment_request_versions', function (Blueprint $table): void {
            $table->foreign('created_by_user_id', 'rrv_creator_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('request_id', 'rrv_request_fk')->references('id')->on('recruitment_requests')->restrictOnDelete();
        });

        Schema::table('recruitment_requests', function (Blueprint $table): void {
            $table->foreign('target_vacancy_id', 'recruitment_requests_target_vacancy_id_foreign')->references('id')->on('vacancies')->restrictOnDelete();
            $table->foreign('actual_requester_position_id', 'rr_actual_position_fk')->references('id')->on('positions')->nullOnDelete();
            $table->foreign('actual_requester_user_id', 'rr_actual_requester_user_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('direct_opened_by_user_id', 'rr_direct_opener_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('requester_user_id', 'rr_requester_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('scope_city_id', 'rr_scope_city_fk')->references('id')->on('cities')->restrictOnDelete();
            $table->foreign('target_position_id', 'rr_target_position_fk')->references('id')->on('positions')->restrictOnDelete();
        });

        Schema::table('recruitment_tags', function (Blueprint $table): void {
            $table->foreign('created_by_user_id', 'rt_creator_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('deleted_by_user_id', 'rt_deleted_by_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('updated_by_user_id', 'rt_updater_fk')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::table('recruitment_workflow_definition_versions', function (Blueprint $table): void {
            $table->foreign('definition_id', 'rwdv_definition_fk')->references('id')->on('recruitment_workflow_definitions')->restrictOnDelete();
        });

        Schema::table('recruitment_workflow_definitions', function (Blueprint $table): void {
            $table->foreign('created_by_user_id', 'rwd_creator_fk')->references('id')->on('users')->restrictOnDelete();
        });

        Schema::table('recruitment_workflow_instance_steps', function (Blueprint $table): void {
            $table->foreign('acted_by_user_id', 'rwis_actor_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('assigned_user_id', 'rwis_assignee_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('workflow_instance_id', 'rwis_instance_fk')->references('id')->on('recruitment_workflow_instances')->cascadeOnDelete();
            $table->foreign('workflow_step_id', 'rwis_workflow_step_fk')->references('id')->on('recruitment_workflow_steps')->restrictOnDelete();
        });

        Schema::table('recruitment_workflow_instances', function (Blueprint $table): void {
            $table->foreign('definition_version_id', 'rwi_definition_version_fk')->references('id')->on('recruitment_workflow_definition_versions')->restrictOnDelete();
            $table->foreign('request_id', 'rwi_request_fk')->references('id')->on('recruitment_requests')->restrictOnDelete();
            $table->foreign('request_version_id', 'rwi_request_version_fk')->references('id')->on('recruitment_request_versions')->restrictOnDelete();
        });

        Schema::table('recruitment_workflow_steps', function (Blueprint $table): void {
            $table->foreign('definition_version_id', 'rws_definition_version_fk')->references('id')->on('recruitment_workflow_definition_versions')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('recruitment_workflow_steps', function (Blueprint $table): void {
            $table->dropForeign('rws_definition_version_fk');
        });

        Schema::table('recruitment_workflow_instances', function (Blueprint $table): void {
            $table->dropForeign('rwi_definition_version_fk');
            $table->dropForeign('rwi_request_fk');
            $table->dropForeign('rwi_request_version_fk');
        });

        Schema::table('recruitment_workflow_instance_steps', function (Blueprint $table): void {
            $table->dropForeign('rwis_actor_fk');
            $table->dropForeign('rwis_assignee_fk');
            $table->dropForeign('rwis_instance_fk');
            $table->dropForeign('rwis_workflow_step_fk');
        });

        Schema::table('recruitment_workflow_definitions', function (Blueprint $table): void {
            $table->dropForeign('rwd_creator_fk');
        });

        Schema::table('recruitment_workflow_definition_versions', function (Blueprint $table): void {
            $table->dropForeign('rwdv_definition_fk');
        });

        Schema::table('recruitment_tags', function (Blueprint $table): void {
            $table->dropForeign('rt_creator_fk');
            $table->dropForeign('rt_deleted_by_fk');
            $table->dropForeign('rt_updater_fk');
        });

        Schema::table('recruitment_requests', function (Blueprint $table): void {
            $table->dropForeign('recruitment_requests_target_vacancy_id_foreign');
            $table->dropForeign('rr_actual_position_fk');
            $table->dropForeign('rr_actual_requester_user_fk');
            $table->dropForeign('rr_direct_opener_fk');
            $table->dropForeign('rr_requester_fk');
            $table->dropForeign('rr_scope_city_fk');
            $table->dropForeign('rr_target_position_fk');
        });

        Schema::table('recruitment_request_versions', function (Blueprint $table): void {
            $table->dropForeign('rrv_creator_fk');
            $table->dropForeign('rrv_request_fk');
        });

        Schema::table('recruitment_request_position_details', function (Blueprint $table): void {
            $table->dropForeign('rrpd_center_fk');
            $table->dropForeign('rrpd_contract_type_fk');
            $table->dropForeign('rrpd_department_fk');
            $table->dropForeign('rrpd_job_level_fk');
            $table->dropForeign('rrpd_position_fk');
            $table->dropForeign('rrpd_program_fk');
            $table->dropForeign('rrpd_request_version_fk');
            $table->dropForeign('rrpd_supervisor_fk');
        });

        Schema::table('recruitment_request_external_confirmations', function (Blueprint $table): void {
            $table->dropForeign('rrec_confirmer_position_fk');
            $table->dropForeign('rrec_confirmer_user_fk');
            $table->dropForeign('rrec_recorder_fk');
            $table->dropForeign('rrec_request_fk');
        });

        Schema::table('recruitment_request_events', function (Blueprint $table): void {
            $table->dropForeign('rre_actor_fk');
            $table->dropForeign('rre_request_fk');
            $table->dropForeign('rre_request_version_fk');
            $table->dropForeign('rre_workflow_instance_fk');
            $table->dropForeign('rre_workflow_step_fk');
        });

        Schema::table('recruitment_request_comments', function (Blueprint $table): void {
            $table->dropForeign('rrc_request_fk');
            $table->dropForeign('rrc_user_fk');
            $table->dropForeign('rrc_version_fk');
        });

        Schema::table('recruitment_rejection_notices', function (Blueprint $table): void {
            $table->dropForeign('rrn_application_fk');
            $table->dropForeign('rrn_decision_fk');
            $table->dropForeign('rrn_requester_fk');
        });

        Schema::table('recruitment_privacy_audits', function (Blueprint $table): void {
            $table->dropForeign('rpa_actor_fk');
        });

        Schema::table('recruitment_job_offers', function (Blueprint $table): void {
            $table->dropForeign('recruitment_job_offers_current_version_id_foreign');
            $table->dropForeign('rjo_application_fk');
            $table->dropForeign('rjo_canceller_fk');
            $table->dropForeign('rjo_creator_fk');
            $table->dropForeign('rjo_position_fk');
            $table->dropForeign('rjo_responder_fk');
            $table->dropForeign('rjo_updater_fk');
            $table->dropForeign('rjo_vacancy_fk');
        });

        Schema::table('recruitment_job_offer_versions', function (Blueprint $table): void {
            $table->dropForeign('rjov_contract_type_fk');
            $table->dropForeign('rjov_creator_fk');
            $table->dropForeign('rjov_job_offer_fk');
            $table->dropForeign('rjov_updater_fk');
        });

        Schema::table('recruitment_interviews', function (Blueprint $table): void {
            $table->dropForeign('ri_application_fk');
            $table->dropForeign('ri_canceller_fk');
            $table->dropForeign('ri_creator_fk');
            $table->dropForeign('ri_eval_closer_fk');
            $table->dropForeign('ri_outcome_recorder_fk');
            $table->dropForeign('ri_updater_fk');
        });

        Schema::table('recruitment_interview_reschedules', function (Blueprint $table): void {
            $table->dropForeign('rir_actor_fk');
            $table->dropForeign('rir_interview_fk');
        });

        Schema::table('recruitment_interview_required_participants', function (Blueprint $table): void {
            $table->dropForeign('rirp_attendee_fk');
            $table->dropForeign('rirp_interview_fk');
            $table->dropForeign('rirp_owner_fk');
        });

        Schema::table('recruitment_interview_participants', function (Blueprint $table): void {
            $table->dropForeign('rip_adder_fk');
            $table->dropForeign('rip_interview_fk');
            $table->dropForeign('rip_remover_fk');
            $table->dropForeign('rip_user_fk');
        });

        Schema::table('recruitment_interview_notices', function (Blueprint $table): void {
            $table->dropForeign('rin_interview_fk');
            $table->dropForeign('rin_requester_fk');
        });

        Schema::table('recruitment_interview_evaluations', function (Blueprint $table): void {
            $table->dropForeign('rie_interview_fk');
            $table->dropForeign('rie_reviewer_fk');
            $table->dropForeign('rie_updater_fk');
            $table->dropForeign('rie_withdrawer_fk');
        });

        Schema::table('recruitment_interview_evaluation_revisions', function (Blueprint $table): void {
            $table->dropForeign('rier_actor_fk');
            $table->dropForeign('rier_evaluation_fk');
        });

        Schema::table('recruitment_interview_evaluation_notes', function (Blueprint $table): void {
            $table->dropForeign('ri_eval_notes_author_fk');
            $table->dropForeign('ri_eval_notes_evaluation_fk');
        });

        Schema::table('recruitment_indicator_settings', function (Blueprint $table): void {
            $table->dropForeign('recruitment_indicator_settings_updated_by_user_id_foreign');
        });

        Schema::table('recruitment_hiring_conversions', function (Blueprint $table): void {
            $table->dropForeign('rhc_applicant_fk');
            $table->dropForeign('rhc_contract_fk');
            $table->dropForeign('rhc_converter_fk');
            $table->dropForeign('rhc_employee_fk');
            $table->dropForeign('rhc_job_offer_fk');
            $table->dropForeign('rhc_offer_version_fk');
        });

        Schema::table('recruitment_cv_analyses', function (Blueprint $table): void {
            $table->dropForeign('rca_application_fk');
            $table->dropForeign('rca_file_fk');
        });

        Schema::table('recruitment_campaign_invites', function (Blueprint $table): void {
            $table->dropForeign('rci_campaign_fk');
            $table->dropForeign('rci_creator_fk');
        });

        Schema::table('recruitment_applications', function (Blueprint $table): void {
            $table->dropForeign('rapp_applicant_fk');
            $table->dropForeign('rapp_campaign_fk');
            $table->dropForeign('rapp_form_version_fk');
            $table->dropForeign('rapp_invite_fk');
            $table->dropForeign('rapp_public_link_fk');
            $table->dropForeign('rapp_vacancy_fk');
        });

        Schema::table('recruitment_application_tags', function (Blueprint $table): void {
            $table->dropForeign('rat_application_fk');
            $table->dropForeign('rat_creator_fk');
            $table->dropForeign('rat_tag_fk');
        });

        Schema::table('recruitment_application_reviews', function (Blueprint $table): void {
            $table->dropForeign('ra_reviews_application_fk');
            $table->dropForeign('ra_reviews_reviewer_fk');
        });

        Schema::table('recruitment_application_participants', function (Blueprint $table): void {
            $table->dropForeign('ra_participants_added_by_fk');
            $table->dropForeign('ra_participants_applicant_fk');
            $table->dropForeign('ra_participants_application_fk');
            $table->dropForeign('ra_participants_removed_by_fk');
            $table->dropForeign('ra_participants_user_fk');
        });

        Schema::table('recruitment_application_indicator_snapshots', function (Blueprint $table): void {
            $table->dropForeign('rais_application_fk');
            $table->dropForeign('rais_computed_by_fk');
            $table->dropForeign('rais_criteria_fk');
        });

        Schema::table('recruitment_application_flags', function (Blueprint $table): void {
            $table->dropForeign('recruitment_application_flags_application_id_foreign');
            $table->dropForeign('recruitment_application_flags_created_by_user_id_foreign');
        });

        Schema::table('recruitment_application_endorsements', function (Blueprint $table): void {
            $table->dropForeign('raen_application_fk');
            $table->dropForeign('raen_user_fk');
        });

        Schema::table('recruitment_application_decisions', function (Blueprint $table): void {
            $table->dropForeign('rad_application_fk');
            $table->dropForeign('rad_decider_fk');
        });

        Schema::table('recruitment_application_answers', function (Blueprint $table): void {
            $table->dropForeign('raa_application_fk');
            $table->dropForeign('raa_form_version_fk');
        });

        Schema::table('recruitment_applicants', function (Blueprint $table): void {
            $table->dropForeign('ra_city_fk');
            $table->dropForeign('ra_employee_fk');
            $table->dropForeign('ra_possible_dup_fk');
        });

        Schema::table('recruitment_applicant_notes', function (Blueprint $table): void {
            $table->dropForeign('ran_applicant_fk');
            $table->dropForeign('ran_application_fk');
            $table->dropForeign('ran_deleted_by_fk');
            $table->dropForeign('ran_user_fk');
        });

        Schema::table('recruitment_applicant_files', function (Blueprint $table): void {
            $table->dropForeign('raf_applicant_fk');
            $table->dropForeign('raf_application_fk');
            $table->dropForeign('raf_archive_note_fk');
            $table->dropForeign('raf_archived_by_fk');
        });

        Schema::table('public_recruitment_links', function (Blueprint $table): void {
            $table->dropForeign('prl_campaign_fk');
            $table->dropForeign('prl_creator_fk');
            $table->dropForeign('prl_form_version_fk');
            $table->dropForeign('prl_post_version_fk');
        });

        Schema::table('application_form_versions', function (Blueprint $table): void {
            $table->dropForeign('afv_form_fk');
        });

        Schema::table('application_forms', function (Blueprint $table): void {
            $table->dropForeign('af_campaign_fk');
            $table->dropForeign('af_creator_fk');
            $table->dropForeign('application_forms_current_version_id_foreign');
        });

        Schema::table('job_post_versions', function (Blueprint $table): void {
            $table->dropForeign('jpv_job_post_fk');
        });

        Schema::table('job_posts', function (Blueprint $table): void {
            $table->dropForeign('job_posts_current_version_id_foreign');
            $table->dropForeign('jp_campaign_fk');
            $table->dropForeign('jp_creator_fk');
        });

        Schema::table('vacancies', function (Blueprint $table): void {
            $table->dropForeign('vac_position_fk');
            $table->dropForeign('vac_request_fk');
        });

        Schema::table('hiring_campaigns', function (Blueprint $table): void {
            $table->dropForeign('hc_closer_fk');
            $table->dropForeign('hc_creator_fk');
            $table->dropForeign('hc_vacancy_fk');
        });

        Schema::table('notification_deliveries', function (Blueprint $table): void {
            $table->dropForeign('notification_deliveries_recipient_id_foreign');
        });

        Schema::table('notification_outbox', function (Blueprint $table): void {
            $table->dropForeign('notification_outbox_user_id_foreign');
        });

        Schema::table('notification_preferences', function (Blueprint $table): void {
            $table->dropForeign('notification_preferences_user_id_foreign');
        });

        Schema::table('push_subscriptions', function (Blueprint $table): void {
            $table->dropForeign('push_subscriptions_user_id_foreign');
        });

        Schema::table('in_app_feedback', function (Blueprint $table): void {
            $table->dropForeign('in_app_feedback_user_id_foreign');
        });

        Schema::table('assistant_conversations', function (Blueprint $table): void {
            $table->dropForeign('assistant_conversations_user_id_foreign');
        });

        Schema::table('saved_table_views', function (Blueprint $table): void {
            $table->dropForeign('saved_table_views_user_id_foreign');
        });

        Schema::table('navigation_preferences', function (Blueprint $table): void {
            $table->dropForeign('navigation_preferences_user_id_foreign');
        });

        Schema::table('dashboard_layouts', function (Blueprint $table): void {
            $table->dropForeign('dashboard_layouts_user_id_foreign');
        });

        Schema::table('news_external_publications', function (Blueprint $table): void {
            $table->dropForeign('news_external_publications_news_id_foreign');
        });

        Schema::table('news_tags', function (Blueprint $table): void {
            $table->dropForeign('news_tags_news_id_foreign');
        });

        Schema::table('news_audience_targets', function (Blueprint $table): void {
            $table->dropForeign('news_audience_targets_news_id_foreign');
        });

        Schema::table('news', function (Blueprint $table): void {
            $table->dropForeign('news_created_by_foreign');
            $table->dropForeign('news_updated_by_foreign');
        });

        Schema::table('folder_user', function (Blueprint $table): void {
            $table->dropForeign('folder_user_folder_id_foreign');
            $table->dropForeign('folder_user_user_id_foreign');
        });

        Schema::table('folders', function (Blueprint $table): void {
            $table->dropForeign('folders_parent_id_foreign');
            $table->dropForeign('folders_user_id_foreign');
        });

        Schema::table('checklists', function (Blueprint $table): void {
            $table->dropForeign('checklists_delivered_id_foreign');
            $table->dropForeign('checklists_employee_id_foreign');
            $table->dropForeign('checklists_receiver_id_foreign');
        });

        Schema::table('profile_change_requests', function (Blueprint $table): void {
            $table->dropForeign('profile_change_requests_decided_by_foreign');
            $table->dropForeign('profile_change_requests_employee_id_foreign');
            $table->dropForeign('profile_change_requests_requested_by_foreign');
        });

        Schema::table('renewal_journeys', function (Blueprint $table): void {
            $table->dropForeign('renewal_journeys_contract_id_foreign');
            $table->dropForeign('renewal_journeys_decided_by_foreign');
            $table->dropForeign('renewal_journeys_employee_id_foreign');
        });

        Schema::table('offboarding_journeys', function (Blueprint $table): void {
            $table->dropForeign('offboarding_journeys_contract_id_foreign');
            $table->dropForeign('offboarding_journeys_employee_id_foreign');
            $table->dropForeign('offboarding_journeys_exit_interview_by_foreign');
            $table->dropForeign('offboarding_journeys_started_by_foreign');
        });

        Schema::table('onboarding_journeys', function (Blueprint $table): void {
            $table->dropForeign('onboarding_journeys_card_handed_over_by_foreign');
            $table->dropForeign('onboarding_journeys_contract_id_foreign');
            $table->dropForeign('onboarding_journeys_employee_id_foreign');
            $table->dropForeign('onboarding_journeys_orientation_completed_by_foreign');
            $table->dropForeign('onboarding_journeys_orientation_scheduled_by_foreign');
        });

        Schema::table('birthday_holiday_choices', function (Blueprint $table): void {
            $table->dropForeign('birthday_holiday_choices_decided_by_foreign');
            $table->dropForeign('birthday_holiday_choices_employee_id_foreign');
            $table->dropForeign('birthday_holiday_choices_holiday_id_foreign');
        });

        Schema::table('holiday_project', function (Blueprint $table): void {
            $table->dropForeign('holiday_project_city_id_foreign');
            $table->dropForeign('holiday_project_holiday_id_foreign');
            $table->dropForeign('holiday_project_program_id_foreign');
        });

        Schema::table('holidays', function (Blueprint $table): void {
            $table->dropForeign('holidays_created_by_foreign');
        });

        Schema::table('leave_request_notes', function (Blueprint $table): void {
            $table->dropForeign('leave_request_notes_leave_request_id_foreign');
        });

        Schema::table('leave_requests', function (Blueprint $table): void {
            $table->dropForeign('leave_requests_alternative_employee_id_foreign');
            $table->dropForeign('leave_requests_approved_by_foreign');
            $table->dropForeign('leave_requests_leave_balance_id_foreign');
        });

        Schema::table('leave_balances', function (Blueprint $table): void {
            $table->dropForeign('leave_balances_contract_id_foreign');
            $table->dropForeign('leave_balances_type_id_foreign');
        });

        Schema::table('default_deliverables', function (Blueprint $table): void {
            $table->dropForeign('default_deliverables_city_id_foreign');
            $table->dropForeign('default_deliverables_employee_id_foreign');
            $table->dropForeign('default_deliverables_item_id_foreign');
        });

        Schema::table('contract_amendment_pay_tiers', function (Blueprint $table): void {
            $table->dropForeign('contract_amendment_pay_tiers_contract_amendment_id_foreign');
        });

        Schema::table('contract_amendments', function (Blueprint $table): void {
            $table->dropForeign('contract_amendments_approved_by_foreign');
            $table->dropForeign('contract_amendments_contract_id_foreign');
            $table->dropForeign('contract_amendments_created_by_foreign');
        });

        Schema::table('contract_pay_tiers', function (Blueprint $table): void {
            $table->dropForeign('contract_pay_tiers_contract_id_foreign');
        });

        Schema::table('contracts', function (Blueprint $table): void {
            $table->dropForeign('contracts_contract_type_id_foreign');
            $table->dropForeign('contracts_employee_id_foreign');
            $table->dropForeign('contracts_position_id_foreign');
            $table->dropForeign('contracts_termination_reason_id_foreign');
        });

        Schema::table('badges', function (Blueprint $table): void {
            $table->dropForeign('badges_employee_id_foreign');
            $table->dropForeign('badges_evaluation_id_foreign');
        });

        Schema::table('evaluations', function (Blueprint $table): void {
            $table->dropForeign('evaluations_contract_id_foreign');
            $table->dropForeign('evaluations_current_manager_id_foreign');
        });

        Schema::table('employee_notes', function (Blueprint $table): void {
            $table->dropForeign('employee_notes_employee_id_foreign');
        });

        Schema::table('employee_items', function (Blueprint $table): void {
            $table->dropForeign('employee_items_employee_id_foreign');
        });

        Schema::table('relatives', function (Blueprint $table): void {
            $table->dropForeign('relatives_employee_id_foreign');
        });

        Schema::table('courses', function (Blueprint $table): void {
            $table->dropForeign('courses_category_id_foreign');
            $table->dropForeign('courses_employee_id_foreign');
        });

        Schema::table('experiences', function (Blueprint $table): void {
            $table->dropForeign('experiences_employee_id_foreign');
        });

        Schema::table('educations', function (Blueprint $table): void {
            $table->dropForeign('educations_employee_id_foreign');
        });

        Schema::table('employees', function (Blueprint $table): void {
            $table->dropForeign('employees_city_id_foreign');
            $table->dropForeign('employees_last_saved_by_foreign');
        });

        Schema::table('positions', function (Blueprint $table): void {
            $table->dropForeign('positions_center_id_foreign');
            $table->dropForeign('positions_city_id_foreign');
            $table->dropForeign('positions_department_id_foreign');
            $table->dropForeign('positions_direct_manager_id_foreign');
            $table->dropForeign('positions_job_level_id_foreign');
            $table->dropForeign('positions_program_id_foreign');
            $table->dropForeign('positions_tech_manager_id_foreign');
        });
    }
};
