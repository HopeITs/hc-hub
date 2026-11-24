<?php

use App\Models\Permission;
use App\Models\Role;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\DB;

/**
 * The 190 permission names the hub database held on 2026-11-20 (194 less the
 * four milestones.* permissions retired with the milestones), when HR's
 * existed only because hub:import-hr had copied them (roadmap 4.1).
 *
 * @return list<string>
 */
function permissionsBeforeTheCatalogHeldHr(): array
{
    return [
        'access-management.manage-employees', 'access-management.manage-permissions', 'access-management.manage-roles', 'access-management.manage-users',
        'access-management.view', 'accounts.create', 'accounts.delete', 'accounts.update',
        'accounts.view', 'activity-log.view', 'ai.draft', 'ai.view-usage',
        'annual-plans.create', 'annual-plans.delete', 'annual-plans.update', 'annual-plans.view',
        'archives.create', 'archives.delete', 'archives.download', 'archives.share',
        'archives.update', 'archives.view', 'badges.create', 'badges.delete',
        'badges.update', 'badges.view', 'calendar.view', 'centers.create',
        'centers.delete', 'centers.export', 'centers.update', 'centers.view',
        'checklists.deliver', 'checklists.manage', 'checklists.receive', 'checklists.view-all',
        'checklists.view-own', 'cities.view', 'contracts.amend', 'contracts.create',
        'contracts.delete', 'contracts.export', 'contracts.update', 'contracts.view',
        'donors.approve', 'donors.create', 'donors.delete', 'donors.update',
        'donors.view', 'employees.create', 'employees.delete', 'employees.export',
        'employees.update', 'employees.view', 'evidence-types.manage', 'funding-agreements.create',
        'funding-agreements.delete', 'funding-agreements.update', 'funding-agreements.view', 'indicator-library.create',
        'indicator-library.delete', 'indicator-library.update', 'indicator-library.view', 'indicators.create',
        'indicators.delete', 'indicators.update', 'indicators.view', 'leaves.create',
        'leaves.decide', 'leaves.delete', 'leaves.export', 'leaves.manage-balances',
        'leaves.review', 'leaves.update', 'leaves.view-all', 'leaves.view-own',
        'meal-coverage.manage', 'narrative-questions.manage', 'news.create', 'news.delete',
        'news.update', 'news.view', 'notification-settings.manage', 'positions.create',
        'positions.delete', 'positions.export', 'positions.update', 'positions.view',
        'programs.create', 'programs.delete', 'programs.update', 'programs.view',
        'project-basics.create', 'project-basics.delete', 'project-basics.export', 'project-basics.update',
        'project-basics.view', 'projects.create', 'projects.delete', 'projects.update',
        'projects.view', 'proposals.approve', 'proposals.correct-status', 'proposals.create',
        'proposals.delete', 'proposals.manage-reviewers', 'proposals.update', 'proposals.view',
        'recruitment-applicants.accept', 'recruitment-applicants.add-notes', 'recruitment-applicants.archive-cv', 'recruitment-applicants.bulk-reject',
        'recruitment-applicants.bulk-tag', 'recruitment-applicants.change-status', 'recruitment-applicants.convert-hires', 'recruitment-applicants.correct-evaluations',
        'recruitment-applicants.delete-notes', 'recruitment-applicants.download-cv', 'recruitment-applicants.endorse', 'recruitment-applicants.export',
        'recruitment-applicants.manage', 'recruitment-applicants.manage-offers', 'recruitment-applicants.manage-tags', 'recruitment-applicants.request-erasure',
        'recruitment-applicants.schedule-interviews', 'recruitment-applicants.tag', 'recruitment-applicants.view', 'recruitment-applicants.view-evaluations',
        'recruitment-applicants.view-offer-salary', 'recruitment-applicants.view-offers', 'recruitment-campaigns.manage', 'recruitment-campaigns.view',
        'recruitment-forms.manage', 'recruitment-forms.view', 'recruitment-indicators.manage', 'recruitment-job-posts.manage',
        'recruitment-job-posts.view', 'recruitment-links.manage', 'recruitment-links.view', 'recruitment-requests.approve',
        'recruitment-requests.approve-finance', 'recruitment-requests.cancel', 'recruitment-requests.direct-open', 'recruitment-requests.export',
        'recruitment-requests.manage', 'recruitment-requests.reject', 'recruitment-requests.request-changes', 'recruitment-requests.submit',
        'recruitment-requests.submit-without-manager', 'recruitment-requests.view', 'recruitment-vacancies.manage', 'recruitment-vacancies.view',
        'report-templates.manage', 'reports.aggregate', 'reports.approve', 'reports.create',
        'reports.delete', 'reports.mark-seen', 'reports.reopen', 'reports.update',
        'reports.view', 'results.create', 'results.delete', 'results.update',
        'results.view', 'risk-library.manage', 'risks.create', 'risks.delete',
        'risks.update', 'risks.view', 'sectors.manage', 'sectors.view',
        'statistics.view', 'strategies.create', 'strategies.delete', 'strategies.update',
        'strategies.view', 'supervisions.manage', 'targets.create', 'targets.delete',
        'targets.update', 'targets.view', 'tasks.create', 'tasks.delete',
        'tasks.update', 'tasks.view', 'transfers.create', 'transfers.delete',
        'transfers.update', 'transfers.view',
    ];
}

test('syncing an empty database creates every permission the hub held before HR joined the catalog', function () {
    DB::table('role_has_permissions')->delete();
    Permission::query()->delete();
    Role::query()->delete();

    $this->artisan('permissions:sync')->assertSuccessful();

    $created = Permission::query()->where('guard_name', 'web')->pluck('name')->all();

    expect(permissionsBeforeTheCatalogHeldHr())->toHaveCount(190)
        ->and(array_values(array_diff(permissionsBeforeTheCatalogHeldHr(), $created)))->toBe([]);
});

test('no built-in role gains an HR permission from its baseline', function (string $role) {
    (new RolesAndPermissionsSeeder)->run();

    $held = Role::findByName($role, 'web')->permissions()->pluck('name')->all();

    expect(array_values(array_intersect($held, [
        'employees.view', 'contracts.view', 'positions.view', 'leaves.view-all', 'archives.view', 'statistics.view',
        'recruitment-applicants.view', 'recruitment-requests.view', 'access-management.view', 'centers.view',
        'employees.update', 'contracts.update', 'recruitment-applicants.view-offer-salary',
    ])))->toBe([]);
})->with(['Team Member', 'Media Team', 'Public Relations', 'Public Relations Manager', 'MEAL', 'Financial', 'Managers', 'General Manager']);

test('Super Admin holds the HR permissions on a fresh database', function () {
    (new RolesAndPermissionsSeeder)->run();

    expect(Role::findByName('Super Admin', 'web')->permissions()->pluck('name')->all())
        ->toContain('employees.view', 'contracts.amend', 'recruitment-applicants.request-erasure');
});
