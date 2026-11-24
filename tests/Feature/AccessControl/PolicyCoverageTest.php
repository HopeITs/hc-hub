<?php

use App\Models\AccessGrant;
use App\Models\Activity;
use App\Models\AiRequest;
use App\Models\KnownDevice;
use App\Models\Media;
use App\Models\NotificationMute;
use App\Models\Permission;
use App\Models\Role;
use App\Models\TrustedDevice;
use App\Models\UserNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Modules\Core\Models\Center as HrCenter;
use Modules\Core\Models\City as HrCity;
use Modules\Hr\Models\ApplicationFormVersion;
use Modules\Hr\Models\AttendanceDayDecision;
use Modules\Hr\Models\AttendanceDeviceLink;
use Modules\Hr\Models\AttendanceImport;
use Modules\Hr\Models\AttendanceMonth;
use Modules\Hr\Models\AttendancePunch;
use Modules\Hr\Models\ContractAmendmentPayTier;
use Modules\Hr\Models\ContractPayTier;
use Modules\Hr\Models\Course;
use Modules\Hr\Models\Education;
use Modules\Hr\Models\EmployeeNote;
use Modules\Hr\Models\Evaluation;
use Modules\Hr\Models\Experience;
use Modules\Hr\Models\HolidayProject;
use Modules\Hr\Models\JobPostVersion;
use Modules\Hr\Models\LeaveRequestNote;
use Modules\Hr\Models\MailList;
use Modules\Hr\Models\MailListMember;
use Modules\Hr\Models\NewsAudienceTarget;
use Modules\Hr\Models\NewsExternalPublication;
use Modules\Hr\Models\NewsTag;
use Modules\Hr\Models\OffboardingJourney;
use Modules\Hr\Models\OnboardingJourney;
use Modules\Hr\Models\PolicyDocument;
use Modules\Hr\Models\ProfileChangeRequest;
use Modules\Hr\Models\RecruitmentApplicationAnswer;
use Modules\Hr\Models\RecruitmentApplicationDecision;
use Modules\Hr\Models\RecruitmentApplicationEndorsement;
use Modules\Hr\Models\RecruitmentApplicationFlag;
use Modules\Hr\Models\RecruitmentApplicationIndicatorSnapshot;
use Modules\Hr\Models\RecruitmentApplicationParticipant;
use Modules\Hr\Models\RecruitmentApplicationTag;
use Modules\Hr\Models\RecruitmentCvAnalysis;
use Modules\Hr\Models\RecruitmentIndicatorCriteriaVersion;
use Modules\Hr\Models\RecruitmentIndicatorSetting;
use Modules\Hr\Models\RecruitmentInterviewEvaluationNote;
use Modules\Hr\Models\RecruitmentInterviewEvaluationRevision;
use Modules\Hr\Models\RecruitmentInterviewNotice;
use Modules\Hr\Models\RecruitmentInterviewParticipant;
use Modules\Hr\Models\RecruitmentInterviewRequiredParticipant;
use Modules\Hr\Models\RecruitmentInterviewReschedule;
use Modules\Hr\Models\RecruitmentJobOfferVersion;
use Modules\Hr\Models\RecruitmentPrivacyAudit;
use Modules\Hr\Models\RecruitmentRejectionNotice;
use Modules\Hr\Models\RecruitmentRequestComment;
use Modules\Hr\Models\RecruitmentRequestExternalConfirmation;
use Modules\Hr\Models\RecruitmentRequestPositionDetail;
use Modules\Hr\Models\RecruitmentRequestVersion;
use Modules\Hr\Models\RecruitmentWorkflowDefinition;
use Modules\Hr\Models\RecruitmentWorkflowDefinitionVersion;
use Modules\Hr\Models\RecruitmentWorkflowInstance;
use Modules\Hr\Models\RecruitmentWorkflowStep;
use Modules\Hr\Models\Relative;
use Modules\Hr\Models\RenewalJourney;
use Modules\Tracking\Models\Account;
use Modules\Tracking\Models\ActivityPlanBatch;
use Modules\Tracking\Models\ActivityPlanFollowUp;
use Modules\Tracking\Models\ActivityPlanItem;
use Modules\Tracking\Models\ActivityPlanItemCity;
use Modules\Tracking\Models\ActivityPlanMilestone;
use Modules\Tracking\Models\DonorProposal;
use Modules\Tracking\Models\FundingAgreementInstallment;
use Modules\Tracking\Models\Indicator;
use Modules\Tracking\Models\IndicatorProgress;
use Modules\Tracking\Models\MonthlyReportActivityEntry;
use Modules\Tracking\Models\MonthlyReportAnswer;
use Modules\Tracking\Models\MonthlyReportFile;
use Modules\Tracking\Models\MonthlyReportLine;
use Modules\Tracking\Models\MonthlyReportLineBreakdown;
use Modules\Tracking\Models\MonthlyReportLineShare;
use Modules\Tracking\Models\MonthlyReportReopenRequest;
use Modules\Tracking\Models\PlanIndicator;
use Modules\Tracking\Models\PlanUpdateFile;
use Modules\Tracking\Models\PlanUpdateNumber;
use Modules\Tracking\Models\PlanUpdateQuestion;
use Modules\Tracking\Models\PlanUpdateWeek;
use Modules\Tracking\Models\ProjectActivityStatus;
use Modules\Tracking\Models\ProposalChangeRequest;
use Modules\Tracking\Models\ProposalChangeRequestReview;
use Modules\Tracking\Models\ProposalLocationCenter;
use Modules\Tracking\Models\ProposalReviewer;
use Modules\Tracking\Models\ProposalStatusNote;
use Modules\Tracking\Models\ReportFile;
use Modules\Tracking\Models\ReportTemplateLine;
use Modules\Tracking\Models\ReportTemplateQuestion;
use Modules\Tracking\Models\Result;
use Modules\Tracking\Models\ScheduleBaseline;
use Modules\Tracking\Models\ScheduleBaselineItem;
use Modules\Tracking\Models\ScheduleDependency;
use Modules\Tracking\Models\StrategicPlanTarget;
use Modules\Tracking\Models\StrategicPlanYear;
use Modules\Tracking\Models\TaskAssignee;
use Modules\Tracking\Models\TaskAttachment;
use Modules\Tracking\Models\TransfersTracking;

/*
| Every kind of record the application stores has a decided access rule:
| either a policy class, or an entry below naming the permission module
| that guards it (module => null for records only ever touched by their
| own user or never written here), with the reason. A new model added
| without either fails this test, so nobody forgets to decide.
*/

/**
 * Models without a policy: model class => [permission module or null, why].
 *
 * @return array<class-string<Model>, array{0: string|null, 1: string}>
 */
function modelsGuardedWithoutPolicy(): array
{
    return [
        AccessGrant::class => ['access-management', 'Scoped grants: written only by GrantScopedAccess and RevokeScopedAccess under access-management.manage-permissions, read by AccessScope.'],
        ActivityPlanBatch::class => ['annual-plans', 'Part of its annual plan (ActivityPlanPolicy::update); written only by SaveActivityPlanItem and CopyActivityPlanItemSettings.'],
        ActivityPlanMilestone::class => ['annual-plans', 'A checkpoint of a live annual plan: read under ManagePlanMilestones::mayView, written only by ManagePlanMilestones under mayManage.'],
        ActivityPlanFollowUp::class => ['annual-plans', 'Part of its annual plan (ActivityPlanPolicy::review); written only by ManageActivityPlanFollowUps.'],
        ActivityPlanItem::class => ['annual-plans', 'Part of its annual plan (ActivityPlanPolicy::update); written only by the Modules\Tracking\Actions\AnnualPlans actions.'],
        ActivityPlanItemCity::class => ['annual-plans', 'Part of its annual plan item (ActivityPlanPolicy::update); written only by SaveActivityPlanItem.'],
        AiRequest::class => ['ai', 'The AI usage ledger: written only by App\Support\Ai\AiGateway, read only under ai.view-usage.'],
        Account::class => ['accounts', 'accounts.* permissions on the admin accounts page and the transfer forms.'],
        Activity::class => ['activity-log', 'The global log needs activity-log.view; a record\'s own Activity tab follows that record\'s page.'],
        DonorProposal::class => ['funding-agreements', 'funding-agreements.* permissions (ManagesAgreementForm).'],
        FundingAgreementInstallment::class => ['funding-agreements', 'The payment schedule of an agreement: written only by SaveAgreementInstallments under funding-agreements.update (T09).'],
        HrCenter::class => [null, 'Read-only mirror of the HR database; never written here.'],
        HrCity::class => ['cities', 'cities.* permissions on the admin cities page.'],
        ApplicationFormVersion::class => ['recruitment-forms', 'A version of its application form: written only by RecruitmentApplicationFormService under ApplicationFormPolicy (create, manage).'],
        AttendanceDayDecision::class => ['attendance', 'Part of its attendance month: written only from the timesheets page after the Roster and attendance.manage check (mayDecide).'],
        AttendanceDeviceLink::class => ['attendance', 'Written only by AttendanceImporter from the upload page under attendance.manage.'],
        AttendanceImport::class => ['attendance', 'Written only by AttendanceImporter from the upload page under attendance.manage.'],
        AttendanceMonth::class => ['attendance', 'Moved only by the MonthWorkflow abilities (attendance.view-own for the employee\'s own month, Roster and attendance.manage for others).'],
        AttendancePunch::class => ['attendance', 'Part of its attendance import: written only by AttendanceImporter under attendance.manage.'],
        ContractAmendmentPayTier::class => ['contracts', 'Part of its amendment: written only by ContractAmendmentService under ContractPolicy (amend, decideAmendment).'],
        ContractPayTier::class => ['contracts', 'Part of its contract (ContractPolicy): rewritten only by ContractAmendmentService when an amendment is applied.'],
        Course::class => ['employees', 'Part of the employee profile (EmployeePolicy): copied in by hub:import-hr, never written here; files go through EmployeeFileController.'],
        Education::class => ['employees', 'Part of the employee profile (EmployeePolicy): copied in by hub:import-hr, never written here; files go through EmployeeFileController.'],
        EmployeeNote::class => [null, 'The old notes table: copied in by hub:import-hr and read once by App\Support\Notes\CopyEmployeeNotes; never written here.'],
        Evaluation::class => ['employees', 'Part of the employee profile (EmployeePolicy): copied in by hub:import-hr, never written here.'],
        Experience::class => ['employees', 'Part of the employee profile (EmployeePolicy): copied in by hub:import-hr, never written here; files go through EmployeeFileController.'],
        HolidayProject::class => ['leaves', 'Part of its holiday: written only by SaveHoliday and DeleteHoliday under HolidayPolicy.'],
        JobPostVersion::class => ['recruitment-job-posts', 'A version of its job post: written only by RecruitmentJobPostService under JobPostPolicy (create, manage).'],
        LeaveRequestNote::class => ['leaves', 'Part of its leave request: written only by LeaveRequestReview under LeaveRequestPolicy (review, object), removed with it by DeleteLeaveRequest.'],
        MailList::class => ['mail-lists', 'Loaded by mail-lists:load (MailListImporter) and edited only on the mail-lists page under mail-lists.manage.'],
        MailListMember::class => ['mail-lists', 'Part of its mail list: written only on the mail-lists page under mail-lists.manage and mail-lists.confirm.'],
        NewsAudienceTarget::class => ['news', 'Part of its news item: written only by SaveNews under NewsPolicy.'],
        NewsExternalPublication::class => ['news', 'Part of its news item (NewsPolicy): copied in by hub:import-hr, never written here.'],
        NewsTag::class => ['news', 'Part of its news item: written only by SaveNews under NewsPolicy.'],
        OffboardingJourney::class => ['employees', 'Part of its employee: written only from the journeys page under EmployeePolicy::update and by the scheduled journey commands.'],
        OnboardingJourney::class => ['employees', 'Part of its employee: written only from the journeys page under EmployeePolicy::update and by the scheduled journey commands.'],
        PolicyDocument::class => [null, 'Copied in by hub:import-hr; nothing here reads or writes it.'],
        ProfileChangeRequest::class => ['employees', 'Part of its employee: copied in by hub:import-hr, listed only in the work inbox under employees.update, never written here.'],
        RecruitmentApplicationAnswer::class => ['recruitment-applicants', 'Part of its application: written only by PublicRecruitmentApplicationService through the public link, read under RecruitmentApplicationPolicy.'],
        RecruitmentApplicationDecision::class => ['recruitment-applicants', 'Part of its application: written only by RecruitmentApplicationStatusService under RecruitmentApplicationPolicy (changeStatus, bulkReject).'],
        RecruitmentApplicationEndorsement::class => ['recruitment-applicants', 'Part of its application: written only by RecruitmentEndorsementService, which refuses anyone off the review panel.'],
        RecruitmentApplicationFlag::class => ['recruitment-applicants', 'Part of its application: written only by RecruitmentApplicationFlagService under RecruitmentApplicationPolicy::tag.'],
        RecruitmentApplicationIndicatorSnapshot::class => ['recruitment-indicators', 'Part of its application (RecruitmentApplicationPolicy): written only by RecruitmentMatchingIndicators.'],
        RecruitmentApplicationParticipant::class => ['recruitment-applicants', 'Part of its application: written only by RecruitmentApplicationParticipantService under RecruitmentApplicationPolicy (manageReviewParticipants, bulkManageReviewParticipants).'],
        RecruitmentApplicationTag::class => ['recruitment-applicants', 'Part of its application: written only by RecruitmentTagService under RecruitmentApplicationPolicy (tag, bulkTag).'],
        RecruitmentCvAnalysis::class => ['recruitment-applicants', 'Part of its application (RecruitmentApplicationPolicy): written only by RecruitmentCvAnalysisService from the AnalyzeRecruitmentCv job.'],
        RecruitmentIndicatorCriteriaVersion::class => ['recruitment-indicators', 'Written only by RecruitmentMatchingIndicators.'],
        RecruitmentIndicatorSetting::class => ['recruitment-indicators', 'Written only by RecruitmentIndicatorRules from the rules page under recruitment-indicators.manage.'],
        RecruitmentInterviewEvaluationNote::class => ['recruitment-applicants', 'Part of its evaluation: written only by RecruitmentInterviewEvaluationService under RecruitmentInterviewEvaluationPolicy.'],
        RecruitmentInterviewEvaluationRevision::class => ['recruitment-applicants', 'Part of its evaluation: written only by RecruitmentInterviewEvaluationService under RecruitmentInterviewEvaluationPolicy.'],
        RecruitmentInterviewNotice::class => ['recruitment-applicants', 'Part of its interview (RecruitmentInterviewPolicy): written only by RecruitmentInterviewNoticeService from scheduling and the reminder job.'],
        RecruitmentInterviewParticipant::class => ['recruitment-applicants', 'Part of its interview: written only by RecruitmentInterviewSchedulingService under RecruitmentInterviewPolicy (schedule, manage).'],
        RecruitmentInterviewRequiredParticipant::class => ['recruitment-applicants', 'Part of its interview: written only by RecruitmentInterviewSchedulingService under RecruitmentInterviewPolicy (schedule, manage).'],
        RecruitmentInterviewReschedule::class => ['recruitment-applicants', 'Part of its interview: written only by RecruitmentInterviewSchedulingService under RecruitmentInterviewPolicy (schedule, manage).'],
        RecruitmentJobOfferVersion::class => ['recruitment-applicants', 'A version of its job offer: written only by RecruitmentJobOfferService under RecruitmentJobOfferPolicy (create, manage).'],
        RecruitmentPrivacyAudit::class => ['recruitment-applicants', 'Append-only: written only by RecruitmentPrivacyAuditor on export and erasure (RecruitmentApplicantPolicy); the model refuses update and delete.'],
        RecruitmentRejectionNotice::class => ['recruitment-applicants', 'Part of its application: written only by RecruitmentRejectionNoticeService after a rejection by RecruitmentApplicationStatusService, and by its job.'],
        RecruitmentRequestComment::class => ['recruitment-requests', 'Part of its recruitment request (RecruitmentRequestPolicy): copied in by hub:import-hr, never written here.'],
        RecruitmentRequestExternalConfirmation::class => ['recruitment-requests', 'Part of its recruitment request: written only by RecruitmentDirectOpenService under the directOpen ability.'],
        RecruitmentRequestPositionDetail::class => ['recruitment-requests', 'Part of its recruitment request: written only by RecruitmentRequestSubmissionService under RecruitmentRequestPolicy (submit, cancel).'],
        RecruitmentRequestVersion::class => ['recruitment-requests', 'A version of its recruitment request: written only by RecruitmentRequestSubmissionService under RecruitmentRequestPolicy (submit, cancel).'],
        RecruitmentWorkflowDefinition::class => ['recruitment-requests', 'The approval template: copied in by hub:import-hr and read by RecruitmentWorkflowInstanceService; never edited from a page.'],
        RecruitmentWorkflowDefinitionVersion::class => ['recruitment-requests', 'The approval template: copied in by hub:import-hr and read by RecruitmentWorkflowInstanceService; never edited from a page.'],
        RecruitmentWorkflowInstance::class => ['recruitment-requests', 'Part of its recruitment request: written only by RecruitmentWorkflowInstanceService from the submission, direct-open and approval actions under RecruitmentRequestPolicy.'],
        RecruitmentWorkflowStep::class => ['recruitment-requests', 'The approval template: copied in by hub:import-hr and read by RecruitmentWorkflowInstanceService; never edited from a page.'],
        Relative::class => ['employees', 'Part of the employee profile (EmployeePolicy): copied in by hub:import-hr, never written here.'],
        RenewalJourney::class => ['employees', 'Part of its contract: written only from the journeys page under ContractPolicy::manageAutoRenew and by the scheduled journey commands.'],
        Indicator::class => ['indicators', 'indicators.* permissions on the indicators page.'],
        IndicatorProgress::class => ['indicators', 'Readings are recorded under indicators.* permissions.'],
        KnownDevice::class => [null, 'The signed-in user\'s own sign-in devices only.'],
        Media::class => [null, 'Files follow their owner\'s policy (project, proposal) and the download controllers check it.'],
        MonthlyReportActivityEntry::class => ['reports', 'Part of its monthly report (MonthlyReportPolicy::update); written only by SaveMonthlyReportActivities.'],
        MonthlyReportAnswer::class => ['reports', 'Part of its monthly report (MonthlyReportPolicy::update); written only by SaveMonthlyReportAnswers.'],
        MonthlyReportFile::class => ['reports', 'Part of its monthly report (MonthlyReportPolicy::update, fillMealLines); loaded through the report and downloaded through T17B\'s protected route.'],
        MonthlyReportLine::class => ['reports', 'Part of its monthly report (MonthlyReportPolicy::update, fillMealLines); loaded through the report.'],
        MonthlyReportLineBreakdown::class => ['reports', 'Part of its monthly report line (MonthlyReportPolicy::update, fillMealLines); loaded through the line.'],
        MonthlyReportLineShare::class => ['reports', 'Part of its monthly report line (MonthlyReportPolicy::update, fillMealLines); loaded through the line.'],
        MonthlyReportReopenRequest::class => ['reports', 'Raised and decided under MonthlyReportPolicy::requestReopen and decideReopen; never deleted.'],
        NotificationMute::class => [null, 'The signed-in user\'s own notification preferences only.'],
        Permission::class => ['access-management', 'access-management.* permissions and UserPolicy::grantPermission.'],
        PlanUpdateFile::class => ['annual-plans', 'Part of its monthly update (PlanUpdatePolicy); written only by ManagePlanUpdateFiles, opened through plan-updates.files.download.'],
        PlanUpdateNumber::class => ['annual-plans', 'Part of its monthly update (PlanUpdatePolicy::canFill); written only by SavePlanUpdate.'],
        PlanUpdateQuestion::class => ['annual-plans', 'Part of its monthly update (PlanUpdatePolicy); written only by ReviewPlanUpdate::ask and answer.'],
        PlanUpdateWeek::class => ['annual-plans', 'Part of its monthly update (PlanUpdatePolicy::canFill); written only by SavePlanUpdate.'],
        PlanIndicator::class => ['targets', 'targets.* permissions on the targets page.'],
        ProjectActivityStatus::class => ['reports', 'Written only when a monthly report is validated or reopened (MonthlyReportPolicy::validate, decideReopen).'],
        ProposalStatusNote::class => ['proposals', 'Written only through a proposal status change (ProposalPolicy::update).'],
        ProposalChangeRequest::class => ['proposals', 'Written only by the change-request actions under ProposalPolicy::requestChange, decideChange and withdrawChange (Y-09, D-89a).'],
        ProposalChangeRequestReview::class => ['proposals', 'Part of its change request; written only by RequestProposalChange and ReviewProposalChange under ProposalPolicy::reviewChange (Y-09).'],
        ProposalReviewer::class => ['proposals', 'Written only by the review actions under ProposalPolicy::transition, manageReviewers and markReviewed (T07).'],
        ReportFile::class => ['reports', 'Follows its report (ReportPolicy); the download controllers check it.'],
        ReportTemplateLine::class => ['report-templates', 'Part of a report template: written only by the report template actions under ReportTemplatePolicy::manage (T33).'],
        ReportTemplateQuestion::class => ['report-templates', 'Part of a report template: written only by the report template actions under ReportTemplatePolicy::manage (T33).'],
        Result::class => ['results', 'results.* permissions on the results page.'],
        Role::class => ['access-management', 'UserPolicy::manageRole, editRolePermissions and assignRole.'],
        ScheduleBaseline::class => ['projects', 'Part of a project schedule (ProjectPolicy::update).'],
        ScheduleBaselineItem::class => ['projects', 'Part of a project schedule (ProjectPolicy::update).'],
        ScheduleDependency::class => ['projects', 'Part of a project schedule (ProjectPolicy::update).'],
        StrategicPlanTarget::class => ['strategies', 'Part of its strategic plan year: written only by SaveStrategicPlan under StrategicPlanPolicy::write.'],
        StrategicPlanYear::class => ['strategies', 'Part of its strategic plan: written only by SaveStrategicPlan under StrategicPlanPolicy::write.'],
        ProposalLocationCenter::class => ['proposals', 'A center of a proposal location row: written only through SaveProposal and the link wrappers under ProposalLocationPolicy (W-34a).'],
        StrategicPlanTarget::class => ['strategies', 'Part of its strategic plan: written only by SaveStrategicPlan under StrategicPlanPolicy::write.'],
        StrategicPlanYear::class => ['strategies', 'Part of its strategic plan: written only by SaveStrategicPlan under StrategicPlanPolicy::write.'],
        TaskAssignee::class => ['tasks', 'Follows its task (TaskPolicy).'],
        TaskAttachment::class => ['tasks', 'Follows its task (TaskPolicy); the download controllers check it.'],
        TransfersTracking::class => ['transfers', 'transfers.* permissions (ManagesTransferForm).'],
        TrustedDevice::class => [null, 'The signed-in user\'s own trusted browsers only.'],
        UserNotification::class => [null, 'Always read through forUser(): a user only ever reaches their own inbox.'],
    ];
}

/**
 * Every concrete Eloquent model in app/Models and modules/*\/Models.
 *
 * @return list<class-string<Model>>
 */
function everyApplicationModel(): array
{
    $directories = ['App\\Models\\' => app_path('Models')];

    foreach (glob(base_path('modules/*/Models')) ?: [] as $directory) {
        $directories['Modules\\'.basename(dirname($directory)).'\\Models\\'] = $directory;
    }

    return collect($directories)
        ->flatMap(fn (string $directory, string $namespace): array => array_map(
            fn (SplFileInfo $file): string => $namespace.$file->getBasename('.php'),
            File::files($directory),
        ))
        ->filter(fn (string $class): bool => class_exists($class)
            && is_subclass_of($class, Model::class)
            && ! (new ReflectionClass($class))->isAbstract())
        ->values()
        ->all();
}

test('every model has a policy or a recorded access rule', function () {
    $undecided = collect(everyApplicationModel())
        ->filter(fn (string $class): bool => Gate::getPolicyFor($class) === null && ! array_key_exists($class, modelsGuardedWithoutPolicy()))
        ->values()
        ->all();

    expect($undecided)->toBe([], "These models have no policy and no entry in modelsGuardedWithoutPolicy():\n".implode("\n", $undecided));
});

test('a model given a policy is taken off the list', function () {
    $both = collect(array_keys(modelsGuardedWithoutPolicy()))
        ->filter(fn (string $class): bool => class_exists($class) && Gate::getPolicyFor($class) !== null)
        ->values()
        ->all();

    expect($both)->toBe([]);
});

test('every listed model exists and every named module is in the permission catalog', function () {
    foreach (modelsGuardedWithoutPolicy() as $class => [$module]) {
        expect(class_exists($class))->toBeTrue("{$class} no longer exists");

        if ($module !== null) {
            expect(config('access-control.groups'))->toHaveKey($module);
        }
    }
});
