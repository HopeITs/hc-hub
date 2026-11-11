<?php

use App\Models\Activity;
use App\Models\AiRequest;
use Modules\Core\Models\Center as HrCenter;
use Modules\Core\Models\City as HrCity;
use Modules\Hr\Models\Contract as HrContract;
use Modules\Hr\Models\Department as HrDepartment;
use Modules\Hr\Models\Employee as HrEmployee;
use Modules\Hr\Models\JobLevel as HrJobLevel;
use Modules\Hr\Models\Position as HrPosition;
use Modules\Core\Models\Program as HrProgram;
use Modules\Core\Models\User as HrUser;
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
use Modules\Tracking\Models\Account;
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
use Modules\Tracking\Models\ProjectActivityStatus;
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
        AiRequest::class => ['ai', 'The AI usage ledger: written only by App\Support\Ai\AiGateway, read only under ai.view-usage.'],
        Account::class => ['accounts', 'accounts.* permissions on the admin accounts page and the transfer forms.'],
        Activity::class => ['activity-log', 'The global log needs activity-log.view; a record\'s own Activity tab follows that record\'s page.'],
        DonorProposal::class => ['funding-agreements', 'funding-agreements.* permissions (ManagesAgreementForm).'],
        FundingAgreementInstallment::class => ['funding-agreements', 'The payment schedule of an agreement: written only by SaveAgreementInstallments under funding-agreements.update (T09).'],
        HrCenter::class => [null, 'Read-only mirror of the HR database; never written here.'],
        HrCity::class => ['cities', 'cities.* permissions on the admin cities page.'],
        HrContract::class => [null, 'Read-only mirror of the HR database; never written here.'],
        HrDepartment::class => [null, 'Read-only mirror of the HR database; never written here.'],
        HrEmployee::class => [null, 'Read-only mirror of the HR database; never written here.'],
        HrJobLevel::class => [null, 'Read-only mirror of the HR database; never written here.'],
        HrPosition::class => [null, 'Read-only mirror of the HR database; never written here.'],
        HrProgram::class => [null, 'Read-only mirror of the HR database; never written here.'],
        HrUser::class => [null, 'Read-only mirror of the HR database; never written here.'],
        Indicator::class => ['indicators', 'indicators.* permissions on the indicators page.'],
        IndicatorProgress::class => ['indicators', 'Readings are recorded under indicators.* permissions.'],
        KnownDevice::class => [null, 'The signed-in user\'s own sign-in devices only.'],
        Media::class => [null, 'Files follow their owner\'s policy (project, proposal, milestone) and the download controllers check it.'],
        MonthlyReportActivityEntry::class => ['reports', 'Part of its monthly report (MonthlyReportPolicy::update); written only by SaveMonthlyReportActivities.'],
        MonthlyReportAnswer::class => ['reports', 'Part of its monthly report (MonthlyReportPolicy::update); written only by SaveMonthlyReportAnswers.'],
        MonthlyReportFile::class => ['reports', 'Part of its monthly report (MonthlyReportPolicy::update, fillMealLines); loaded through the report and downloaded through T17B\'s protected route.'],
        MonthlyReportLine::class => ['reports', 'Part of its monthly report (MonthlyReportPolicy::update, fillMealLines); loaded through the report.'],
        MonthlyReportLineBreakdown::class => ['reports', 'Part of its monthly report line (MonthlyReportPolicy::update, fillMealLines); loaded through the line.'],
        MonthlyReportLineShare::class => ['reports', 'Part of its monthly report line (MonthlyReportPolicy::update, fillMealLines); loaded through the line.'],
        MonthlyReportReopenRequest::class => ['reports', 'Raised and decided under MonthlyReportPolicy::requestReopen and decideReopen; never deleted.'],
        NotificationMute::class => [null, 'The signed-in user\'s own notification preferences only.'],
        Permission::class => ['access-management', 'access-management.* permissions and UserPolicy::grantPermission.'],
        PlanIndicator::class => ['targets', 'targets.* permissions on the targets page.'],
        ProjectActivityStatus::class => ['reports', 'Written only when a monthly report is validated or reopened (MonthlyReportPolicy::validate, decideReopen).'],
        ProposalStatusNote::class => ['proposals', 'Written only through a proposal status change (ProposalPolicy::update).'],
        ProposalReviewer::class => ['proposals', 'Written only by the review actions under ProposalPolicy::transition, manageReviewers and markReviewed (T07).'],
        ReportFile::class => ['reports', 'Follows its report (ReportPolicy); the download controllers check it.'],
        ReportTemplateLine::class => ['report-templates', 'Part of a report template: written only by the report template actions under ReportTemplatePolicy::manage (T33).'],
        ReportTemplateQuestion::class => ['report-templates', 'Part of a report template: written only by the report template actions under ReportTemplatePolicy::manage (T33).'],
        Result::class => ['results', 'results.* permissions on the results page.'],
        Role::class => ['access-management', 'UserPolicy::manageRole, editRolePermissions and assignRole.'],
        ScheduleBaseline::class => ['projects', 'Part of a project schedule (ProjectPolicy::update).'],
        ScheduleBaselineItem::class => ['projects', 'Part of a project schedule (ProjectPolicy::update).'],
        ScheduleDependency::class => ['projects', 'Part of a project schedule (ProjectPolicy::update).'],
        ProposalLocationCenter::class => ['proposals', 'A center of a proposal location row: written only through SaveProposal and the link wrappers under ProposalLocationPolicy (W-34a).'],
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
