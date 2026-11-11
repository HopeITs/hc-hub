<?php

declare(strict_types=1);

namespace Modules\Hr\Support;

/**
 * The tables the HR module owns, keyed by their name in the HR app's
 * database (without the hchr_ prefix) and valued by their name here.
 *
 * Ordered parents first, so a copy in this order meets every foreign key
 * except the four that point "forward" (recruitment_requests <-> vacancies,
 * job_posts <-> job_post_versions, application_forms <->
 * application_form_versions, recruitment_job_offers <-> their versions).
 * Three HR tables are renamed because the hub already has a table of that
 * name with a different shape: media, notifications and activity_log are
 * kept as hr_media, hr_notifications and hr_activity_log.
 *
 * Not listed: users, programs, cities and centers (Core), the Spatie
 * permission tables (shared with the hub), the framework tables (cache,
 * jobs, sessions, migrations...) and HR's two *_bak_20261019 backups.
 */
final class HrTables
{
    /**
     * @var array<string, string>
     */
    public const array OWNED = [
        'departments' => 'departments',
        'job_levels' => 'job_levels',
        'positions' => 'positions',
        'employees' => 'employees',
        'education_fields' => 'education_fields',
        'educations' => 'educations',
        'experiences' => 'experiences',
        'course_categories' => 'course_categories',
        'courses' => 'courses',
        'relatives' => 'relatives',
        'employee_items' => 'employee_items',
        'employee_notes' => 'employee_notes',
        'contract_types' => 'contract_types',
        'termination_reasons' => 'termination_reasons',
        'contracts' => 'contracts',
        'evaluations' => 'evaluations',
        'badges' => 'badges',
        'policy_documents' => 'policy_documents',
        'contract_pay_tiers' => 'contract_pay_tiers',
        'contract_amendments' => 'contract_amendments',
        'contract_amendment_pay_tiers' => 'contract_amendment_pay_tiers',
        'checklist_items' => 'checklist_items',
        'default_deliverables' => 'default_deliverables',
        'annual_leave_rules' => 'annual_leave_rules',
        'leave_types' => 'leave_types',
        'leave_balances' => 'leave_balances',
        'leave_requests' => 'leave_requests',
        'leave_request_notes' => 'leave_request_notes',
        'holidays' => 'holidays',
        'holiday_project' => 'holiday_project',
        'birthday_holiday_choices' => 'birthday_holiday_choices',
        'onboarding_journeys' => 'onboarding_journeys',
        'offboarding_journeys' => 'offboarding_journeys',
        'renewal_journeys' => 'renewal_journeys',
        'profile_change_requests' => 'profile_change_requests',
        'checklists' => 'checklists',
        'folders' => 'folders',
        'folder_user' => 'folder_user',
        'news' => 'news',
        'news_audience_targets' => 'news_audience_targets',
        'news_tags' => 'news_tags',
        'news_external_publications' => 'news_external_publications',
        'dashboard_layouts' => 'dashboard_layouts',
        'navigation_preferences' => 'navigation_preferences',
        'saved_table_views' => 'saved_table_views',
        'assistant_conversations' => 'assistant_conversations',
        'in_app_feedback' => 'in_app_feedback',
        'push_subscriptions' => 'push_subscriptions',
        'notification_preferences' => 'notification_preferences',
        'notification_outbox' => 'notification_outbox',
        'notification_deliveries' => 'notification_deliveries',
        'task_attempts' => 'task_attempts',
        'recruitment_requests' => 'recruitment_requests',
        'vacancies' => 'vacancies',
        'hiring_campaigns' => 'hiring_campaigns',
        'job_post_versions' => 'job_post_versions',
        'job_posts' => 'job_posts',
        'application_form_versions' => 'application_form_versions',
        'application_forms' => 'application_forms',
        'public_recruitment_links' => 'public_recruitment_links',
        'recruitment_applicants' => 'recruitment_applicants',
        'recruitment_campaign_invites' => 'recruitment_campaign_invites',
        'recruitment_applications' => 'recruitment_applications',
        'recruitment_applicant_notes' => 'recruitment_applicant_notes',
        'recruitment_applicant_files' => 'recruitment_applicant_files',
        'recruitment_application_answers' => 'recruitment_application_answers',
        'recruitment_application_decisions' => 'recruitment_application_decisions',
        'recruitment_application_endorsements' => 'recruitment_application_endorsements',
        'recruitment_application_flags' => 'recruitment_application_flags',
        'recruitment_indicator_criteria_versions' => 'recruitment_indicator_criteria_versions',
        'recruitment_application_indicator_snapshots' => 'recruitment_application_indicator_snapshots',
        'recruitment_application_participants' => 'recruitment_application_participants',
        'recruitment_application_reviews' => 'recruitment_application_reviews',
        'recruitment_tags' => 'recruitment_tags',
        'recruitment_application_tags' => 'recruitment_application_tags',
        'recruitment_cv_analyses' => 'recruitment_cv_analyses',
        'recruitment_job_offers' => 'recruitment_job_offers',
        'recruitment_job_offer_versions' => 'recruitment_job_offer_versions',
        'recruitment_hiring_conversions' => 'recruitment_hiring_conversions',
        'recruitment_indicator_settings' => 'recruitment_indicator_settings',
        'recruitment_interviews' => 'recruitment_interviews',
        'recruitment_interview_evaluations' => 'recruitment_interview_evaluations',
        'recruitment_interview_evaluation_notes' => 'recruitment_interview_evaluation_notes',
        'recruitment_interview_evaluation_revisions' => 'recruitment_interview_evaluation_revisions',
        'recruitment_interview_notices' => 'recruitment_interview_notices',
        'recruitment_interview_participants' => 'recruitment_interview_participants',
        'recruitment_interview_required_participants' => 'recruitment_interview_required_participants',
        'recruitment_interview_reschedules' => 'recruitment_interview_reschedules',
        'recruitment_privacy_audits' => 'recruitment_privacy_audits',
        'recruitment_rejection_notices' => 'recruitment_rejection_notices',
        'recruitment_request_versions' => 'recruitment_request_versions',
        'recruitment_request_comments' => 'recruitment_request_comments',
        'recruitment_workflow_definitions' => 'recruitment_workflow_definitions',
        'recruitment_workflow_definition_versions' => 'recruitment_workflow_definition_versions',
        'recruitment_workflow_instances' => 'recruitment_workflow_instances',
        'recruitment_workflow_steps' => 'recruitment_workflow_steps',
        'recruitment_request_events' => 'recruitment_request_events',
        'recruitment_request_external_confirmations' => 'recruitment_request_external_confirmations',
        'recruitment_request_position_details' => 'recruitment_request_position_details',
        'recruitment_workflow_instance_steps' => 'recruitment_workflow_instance_steps',
        'media' => 'hr_media',
        'notifications' => 'hr_notifications',
        'activity_log' => 'hr_activity_log',
    ];

    /**
     * The hub names of the HR-owned tables, parents first.
     *
     * @return list<string>
     */
    public static function targets(): array
    {
        return array_values(self::OWNED);
    }
}
