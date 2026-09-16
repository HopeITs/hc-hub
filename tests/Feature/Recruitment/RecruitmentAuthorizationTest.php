<?php

namespace Tests\Feature\Recruitment;

use App\Models\ApplicationForm;
use App\Models\City;
use App\Models\Contract;
use App\Models\ContractType;
use App\Models\Department;
use App\Models\HiringCampaign;
use App\Models\JobLevel;
use App\Models\JobPost;
use App\Models\Permission;
use App\Models\Position;
use App\Models\Program;
use App\Models\PublicRecruitmentLink;
use App\Models\RecruitmentRequest;
use App\Models\RecruitmentRequestEvent;
use App\Models\RecruitmentRequestPositionDetail;
use App\Models\RecruitmentRequestVersion;
use App\Models\RecruitmentWorkflowInstanceStep;
use App\Models\Role;
use App\Models\User;
use App\Models\Vacancy;
use App\Support\AccessPermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\PermissionRegistrar;
use Tests\Concerns\CreatesHrFixtures;
use Tests\TestCase;

class RecruitmentAuthorizationTest extends TestCase
{
    use CreatesHrFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Route::middleware(['web', 'auth', 'can:view,recruitmentRequest'])
            ->get('/_test/recruitment/requests/{recruitmentRequest}', fn (RecruitmentRequest $recruitmentRequest): string => (string) $recruitmentRequest->getKey());
    }

    public function test_the_recruitment_permission_catalog_is_seeded_with_role_baselines(): void
    {
        $expected = [
            'recruitment.requests.view',
            'recruitment.requests.create',
            'recruitment.requests.edit',
            'recruitment.requests.submit',
            'recruitment.requests.review',
            'recruitment.requests.approve',
            'recruitment.requests.reject',
            'recruitment.requests.request_changes',
            'recruitment.requests.export',
            'recruitment.vacancies.view',
            'recruitment.vacancies.manage',
            'recruitment.vacancies.close',
            'recruitment.campaigns.view',
            'recruitment.campaigns.manage',
            'recruitment.campaigns.publish',
            'recruitment.campaigns.close',
            'recruitment.job_posts.view',
            'recruitment.job_posts.manage',
            'recruitment.job_posts.publish',
            'recruitment.forms.view',
            'recruitment.forms.manage',
            'recruitment.forms.publish',
            'recruitment.public_links.manage',
            'recruitment.audit.view',
            'recruitment.audit.export',
        ];

        $this->assertEqualsCanonicalizing(
            $expected,
            collect(AccessPermissionCatalog::names())
                ->filter(fn (string $name): bool => str_starts_with($name, 'recruitment.'))
                ->values()
                ->all(),
        );
        $this->assertSame(count($expected), Permission::query()->whereIn('name', $expected)->count());

        $this->assertFalse(Role::findByName('employee')->hasPermissionTo('recruitment.requests.create'));
        $this->assertFalse(Role::findByName('employee')->hasPermissionTo('recruitment.requests.approve'));
        $this->assertFalse(Role::findByName('manager')->hasPermissionTo('recruitment.requests.create'));
        $this->assertTrue(Role::findByName('program-manager')->hasPermissionTo('recruitment.requests.create'));
        $this->assertTrue(Role::findByName('project-manager')->hasPermissionTo('recruitment.requests.create'));
        $this->assertTrue(Role::findByName('general-manager')->hasPermissionTo('recruitment.requests.create'));
        $this->assertTrue(Role::findByName('finance')->hasPermissionTo('recruitment.requests.approve'));
        $this->assertFalse(Role::findByName('finance')->hasPermissionTo('recruitment.requests.create'));
    }

    public function test_only_allowed_creator_roles_can_open_requests(): void
    {
        $city = $this->makeCity('ALP', '001');
        $allowed = ['program-manager', 'project-manager', 'HR-SYR', 'HR-ALP', 'general-manager', 'hr-director'];
        $denied = ['employee', 'manager', 'finance', 'custom-requester', 'HR-XXX'];

        foreach ($allowed as $role) {
            $user = $this->makeUser($city, $role, ['recruitment.requests.create']);

            $this->assertTrue(
                Gate::forUser($user)->allows('create', [RecruitmentRequest::class, $city->id]),
                "Role [{$role}] should be allowed to create recruitment requests.",
            );
        }

        foreach ($denied as $role) {
            $user = $this->makeUser($city, $role, ['recruitment.requests.create']);

            $this->assertFalse(
                Gate::forUser($user)->allows('create', [RecruitmentRequest::class, $city->id]),
                "Role [{$role}] should not be allowed to create recruitment requests.",
            );
        }
    }

    public function test_regional_hr_only_lists_and_opens_requests_in_the_active_city(): void
    {
        $aleppo = $this->makeCity('ALP', '001');
        $homs = $this->makeCity('HMS', '002');
        $hr = $this->makeUser($aleppo, 'HR-ALP', ['recruitment.requests.view']);
        $local = $this->makeRequest($this->makeUser($aleppo, 'employee'), $aleppo);
        $remote = $this->makeRequest($this->makeUser($homs, 'employee'), $homs);

        $this->assertSame([$local->id], RecruitmentRequest::query()->visibleTo($hr)->pluck('id')->all());
        $this->assertTrue(Gate::forUser($hr)->allows('view', $local));
        $this->assertFalse(Gate::forUser($hr)->allows('view', $remote));

        // An attacker can replace the id in the URL, but the route-level policy
        // still rejects the record before its controller/component is reached.
        $this->actingAs($hr)
            ->get("/_test/recruitment/requests/{$remote->id}")
            ->assertForbidden();
    }

    public function test_headquarters_scope_remains_organization_wide(): void
    {
        $syria = $this->makeCity('SYR', '000');
        $homs = $this->makeCity('HMS', '002');
        $hq = $this->makeUser($syria, 'HR-SYR');
        $remote = $this->makeRequest($this->makeUser($homs, 'employee'), $homs);

        $this->assertTrue(Gate::forUser($hq)->allows('view', $remote));
        $this->assertTrue(RecruitmentRequest::query()->visibleTo($hq)->whereKey($remote)->exists());
    }

    public function test_program_manager_scope_comes_from_the_active_contract(): void
    {
        $city = $this->makeCity('ALP', '001');
        [$department, $jobLevel, $contractType] = $this->positionPrerequisites();
        $managedProgram = $this->makeProgram('managed');
        $otherProgram = $this->makeProgram('other');
        $manager = $this->makeUser($city, 'program-manager');
        $this->assignPosition($manager, $city, $managedProgram, $department, $jobLevel, $contractType);

        $inside = $this->makeRequest(
            $this->makeUser($city, 'employee'),
            $city,
            program: $managedProgram,
            department: $department,
        );
        $outside = $this->makeRequest(
            $this->makeUser($city, 'employee'),
            $city,
            program: $otherProgram,
            department: $department,
        );

        $this->assertTrue(Gate::forUser($manager)->allows('review', $inside));
        $this->assertFalse(Gate::forUser($manager)->allows('review', $outside));
        $this->assertEqualsCanonicalizing(
            [$inside->id],
            RecruitmentRequest::query()->visibleTo($manager)->pluck('id')->all(),
        );
    }

    public function test_a_permission_without_a_recognized_scope_only_exposes_own_requests(): void
    {
        $city = $this->makeCity('ALP', '001');
        $reviewer = $this->makeUser($city, 'custom-reviewer', ['recruitment.requests.view']);
        $own = $this->makeRequest($reviewer, $city);
        $foreign = $this->makeRequest($this->makeUser($city, 'employee'), $city);

        $this->assertSame([$own->id], RecruitmentRequest::query()->visibleTo($reviewer)->pluck('id')->all());
        $this->assertTrue(Gate::forUser($reviewer)->allows('view', $own));
        $this->assertFalse(Gate::forUser($reviewer)->allows('view', $foreign));
    }

    public function test_approval_requires_permission_active_role_assignee_and_matching_scope(): void
    {
        $city = $this->makeCity('ALP', '001');
        [$department, $jobLevel, $contractType] = $this->positionPrerequisites();
        $program = $this->makeProgram('finance-program');
        $finance = $this->makeUser($city, 'finance');
        $otherFinance = $this->makeUser($city, 'finance');
        $this->assignPosition($finance, $city, $program, $department, $jobLevel, $contractType);
        $this->assignPosition($otherFinance, $city, $program, $department, $jobLevel, $contractType);
        $request = $this->makeRequest($this->makeUser($city, 'employee'), $city, $program, $department);
        $step = $this->makeWorkflowStep($request, $finance, 'finance', 'department', $department->id);

        $this->assertTrue(Gate::forUser($finance)->allows('approve', $step));
        $this->assertFalse(Gate::forUser($otherFinance)->allows('approve', $step));

        $financeRole = Role::findByName('finance');
        $employeeRole = Role::findByName('employee');
        $finance->assignRole($employeeRole);
        $finance->roles()->updateExistingPivot($employeeRole->id, ['is_active' => false]);
        $finance->setActiveRole($employeeRole->id);
        $finance->unsetRelation('roles');

        $this->assertFalse(Gate::forUser($finance->fresh())->allows('approve', $step));

        $finance->setActiveRole($financeRole->id);
        $financeRole->revokePermissionTo('recruitment.requests.approve');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $finance->unsetRelation('roles');

        $this->assertFalse(Gate::forUser($finance->fresh())->allows('approve', $step));
    }

    public function test_missing_or_unknown_scope_data_fails_closed(): void
    {
        $aleppo = $this->makeCity('ALP', '001');
        $homs = $this->makeCity('HMS', '002');
        $unknownRegionalHr = $this->makeUser($aleppo, 'HR-XXX', [
            'recruitment.requests.view',
            'recruitment.requests.approve',
        ]);
        $remote = $this->makeRequest($this->makeUser($homs, 'employee'), $homs);
        $step = $this->makeWorkflowStep($remote, $unknownRegionalHr, 'HR-XXX', null, null);

        $this->assertFalse(Gate::forUser($unknownRegionalHr)->allows('view', $remote));
        $this->assertFalse(Gate::forUser($unknownRegionalHr)->allows('approve', $step));
    }

    public function test_vacancy_publishing_and_audit_policies_inherit_request_scope(): void
    {
        $aleppo = $this->makeCity('ALP', '001');
        $homs = $this->makeCity('HMS', '002');
        $hr = $this->makeUser($aleppo, 'HR-ALP', [
            'recruitment.requests.view',
            'recruitment.vacancies.view',
            'recruitment.vacancies.close',
            'recruitment.campaigns.view',
            'recruitment.campaigns.publish',
            'recruitment.job_posts.view',
            'recruitment.job_posts.publish',
            'recruitment.forms.view',
            'recruitment.forms.manage',
            'recruitment.public_links.manage',
            'recruitment.audit.view',
        ]);
        [$department, $jobLevel, $contractType] = $this->positionPrerequisites();
        $program = $this->makeProgram('publishing');
        $localPosition = $this->makePosition($aleppo, $program, $department, $jobLevel, 'local');
        $remotePosition = $this->makePosition($homs, $program, $department, $jobLevel, 'remote');
        $localRequest = $this->makeRequest($this->makeUser($aleppo, 'employee'), $aleppo, $program, $department);
        $remoteRequest = $this->makeRequest($this->makeUser($homs, 'employee'), $homs, $program, $department);
        $local = $this->publishingChain($localRequest, $localPosition, $hr);
        $remote = $this->publishingChain($remoteRequest, $remotePosition, $hr);

        $this->assertTrue(Gate::forUser($hr)->allows('close', $local['vacancy']));
        $this->assertTrue(Gate::forUser($hr)->allows('publish', $local['campaign']));
        $this->assertTrue(Gate::forUser($hr)->allows('publish', $local['jobPost']));
        $this->assertTrue(Gate::forUser($hr)->allows('manage', $local['form']));
        $this->assertTrue(Gate::forUser($hr)->allows('manage', $local['link']));
        $this->assertTrue(Gate::forUser($hr)->allows('view', $local['event']));

        $this->assertFalse(Gate::forUser($hr)->allows('close', $remote['vacancy']));
        $this->assertFalse(Gate::forUser($hr)->allows('publish', $remote['campaign']));
        $this->assertFalse(Gate::forUser($hr)->allows('publish', $remote['jobPost']));
        $this->assertFalse(Gate::forUser($hr)->allows('manage', $remote['form']));
        $this->assertFalse(Gate::forUser($hr)->allows('manage', $remote['link']));
        $this->assertFalse(Gate::forUser($hr)->allows('view', $remote['event']));

        $this->assertSame([$local['vacancy']->id], Vacancy::query()->visibleTo($hr)->pluck('id')->all());
    }

    private function makeRequest(
        User $requester,
        City $city,
        ?Program $program = null,
        ?Department $department = null,
    ): RecruitmentRequest {
        $request = RecruitmentRequest::query()->forceCreate([
            'request_number' => 'REQ-'.strtoupper(uniqid()),
            'requester_user_id' => $requester->id,
            'request_type' => 'new_position',
            'mode' => 'normal',
            'status' => 'submitted',
            'scope_city_id' => $city->id,
            'submitted_at' => now(),
        ]);

        if ($program !== null || $department !== null) {
            $version = RecruitmentRequestVersion::query()->forceCreate([
                'request_id' => $request->id,
                'version_no' => 1,
                'snapshot_json' => [],
                'created_by_user_id' => $requester->id,
                'submitted_at' => now(),
            ]);

            RecruitmentRequestPositionDetail::query()->forceCreate([
                'request_version_id' => $version->id,
                'program_id' => $program?->id,
                'department_id' => $department?->id,
                'role_description_ready' => false,
                'finance_consulted' => false,
                'org_chart_updated' => false,
            ]);
        }

        return $request;
    }

    /** @return array{0: Department, 1: JobLevel, 2: ContractType} */
    private function positionPrerequisites(): array
    {
        return [
            Department::query()->forceCreate(['name' => 'Department '.uniqid(), 'code' => strtoupper(uniqid('D'))]),
            JobLevel::query()->forceCreate(['name_en' => 'Manager', 'name_ar' => 'مدير', 'grade' => 2]),
            ContractType::query()->forceCreate(['name_en' => 'Annual', 'name_ar' => 'سنوي']),
        ];
    }

    private function makeProgram(string $prefix): Program
    {
        return Program::query()->forceCreate([
            'name' => ucfirst($prefix),
            'code' => strtoupper(substr($prefix, 0, 4)).uniqid(),
            'slug' => $prefix.'-'.uniqid(),
            'start_date' => now()->subYear(),
            'is_active' => true,
        ]);
    }

    private function assignPosition(
        User $user,
        City $city,
        Program $program,
        Department $department,
        JobLevel $jobLevel,
        ContractType $contractType,
    ): Position {
        $position = $this->makePosition($city, $program, $department, $jobLevel, 'assigned');

        Contract::query()->forceCreate([
            'employee_id' => $user->employee_id,
            'position_id' => $position->id,
            'contract_type_id' => $contractType->id,
            'start_date' => now()->subMonth(),
            'end_date' => now()->addYear(),
            'currency' => 2,
            'salary_per' => 1,
            'weekly_hours' => 40,
            'is_active' => true,
        ]);

        return $position;
    }

    private function makePosition(
        City $city,
        Program $program,
        Department $department,
        JobLevel $jobLevel,
        string $prefix,
    ): Position {
        return Position::query()->forceCreate([
            'program_id' => $program->id,
            'city_id' => $city->id,
            'job_level_id' => $jobLevel->id,
            'department_id' => $department->id,
            'title_en' => ucfirst($prefix).' position',
            'title_ar' => 'منصب '.uniqid(),
            'max_emp' => 5,
            'exist_emp' => 0,
            'is_active' => true,
        ]);
    }

    private function makeWorkflowStep(
        RecruitmentRequest $request,
        User $assignee,
        string $role,
        ?string $scopeType,
        ?int $scopeId,
    ): RecruitmentWorkflowInstanceStep {
        $requestVersion = $request->versions()->first() ?? RecruitmentRequestVersion::query()->forceCreate([
            'request_id' => $request->id,
            'version_no' => 1,
            'snapshot_json' => [],
            'created_by_user_id' => $request->requester_user_id,
            'submitted_at' => now(),
        ]);
        $now = now();
        $definitionId = DB::table('recruitment_workflow_definitions')->insertGetId([
            'name' => 'Workflow '.uniqid(),
            'request_type' => 'new_position',
            'is_active' => true,
            'created_by_user_id' => $request->requester_user_id,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $definitionVersionId = DB::table('recruitment_workflow_definition_versions')->insertGetId([
            'definition_id' => $definitionId,
            'version_no' => 1,
            'rules_json' => json_encode([]),
            'is_active' => true,
            'published_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $workflowStepId = DB::table('recruitment_workflow_steps')->insertGetId([
            'definition_version_id' => $definitionVersionId,
            'step_order' => 1,
            'step_key' => 'approval',
            'role_key' => $role,
            'scope_rule' => $scopeType ?? 'missing',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $instanceId = DB::table('recruitment_workflow_instances')->insertGetId([
            'request_id' => $request->id,
            'request_version_id' => $requestVersion->id,
            'definition_version_id' => $definitionVersionId,
            'status' => 'in_progress',
            'current_step_order' => 1,
            'started_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return RecruitmentWorkflowInstanceStep::query()->forceCreate([
            'workflow_instance_id' => $instanceId,
            'workflow_step_id' => $workflowStepId,
            'assigned_role' => $role,
            'assigned_scope_type' => $scopeType,
            'assigned_scope_id' => $scopeId,
            'assigned_user_id' => $assignee->id,
        ]);
    }

    /** @return array{vacancy: Vacancy, campaign: HiringCampaign, jobPost: JobPost, form: ApplicationForm, link: PublicRecruitmentLink, event: RecruitmentRequestEvent} */
    private function publishingChain(RecruitmentRequest $request, Position $position, User $actor): array
    {
        $vacancy = Vacancy::query()->forceCreate([
            'position_id' => $position->id,
            'created_from_request_id' => $request->id,
            'status' => 'open',
            'needed_count' => 1,
            'filled_count' => 0,
            'open_at' => now(),
        ]);
        $campaign = HiringCampaign::query()->forceCreate([
            'vacancy_id' => $vacancy->id,
            'status' => 'draft',
            'link_mode' => 'public_open',
            'created_by_user_id' => $actor->id,
        ]);
        $jobPost = JobPost::query()->forceCreate([
            'campaign_id' => $campaign->id,
            'status' => 'draft',
            'created_by_user_id' => $actor->id,
        ]);
        $jobPostVersionId = DB::table('job_post_versions')->insertGetId([
            'job_post_id' => $jobPost->id,
            'version_no' => 1,
            'title_en' => 'Vacancy',
            'title_ar' => 'شاغر',
            'public_payload_json' => json_encode([]),
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $form = ApplicationForm::query()->forceCreate([
            'campaign_id' => $campaign->id,
            'status' => 'draft',
            'created_by_user_id' => $actor->id,
        ]);
        $formVersionId = DB::table('application_form_versions')->insertGetId([
            'application_form_id' => $form->id,
            'version_no' => 1,
            'fields_json' => json_encode([]),
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $link = PublicRecruitmentLink::query()->forceCreate([
            'campaign_id' => $campaign->id,
            'job_post_version_id' => $jobPostVersionId,
            'form_version_id' => $formVersionId,
            'token_hash' => hash('sha256', uniqid('', true)),
            'status' => 'active',
            'expires_at' => now()->addWeek(),
            'created_by_user_id' => $actor->id,
        ]);
        $event = RecruitmentRequestEvent::query()->forceCreate([
            'recruitment_request_id' => $request->id,
            'event_type' => 'submitted',
            'actor_user_id' => $request->requester_user_id,
            'actor_role' => 'employee',
        ]);

        return compact('vacancy', 'campaign', 'jobPost', 'form', 'link', 'event');
    }
}
