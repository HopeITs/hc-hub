<?php

use App\Http\Middleware\SetLocale;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Modules\Core\Models\City;
use Modules\Core\Models\Program;
use Modules\Core\Models\User;
use Modules\Hr\Actions\Recruitment\RecruitmentApplicationFormService;
use Modules\Hr\Actions\Recruitment\RecruitmentApprovalService;
use Modules\Hr\Actions\Recruitment\RecruitmentHiringConversionService;
use Modules\Hr\Actions\Recruitment\RecruitmentJobOfferService;
use Modules\Hr\Actions\Recruitment\RecruitmentRequestDraftService;
use Modules\Hr\Actions\Recruitment\RecruitmentRequestSubmissionService;
use Modules\Hr\Enums\ContractPlace;
use Modules\Hr\Enums\ContractType as EmploymentType;
use Modules\Hr\Enums\Currency;
use Modules\Hr\Enums\Gender;
use Modules\Hr\Enums\HiringCampaignStatus;
use Modules\Hr\Enums\LeaveTypeCases;
use Modules\Hr\Enums\MaritalStatus;
use Modules\Hr\Enums\Nationality;
use Modules\Hr\Enums\RecruitmentApplicationStatus;
use Modules\Hr\Enums\RecruitmentRequestMode;
use Modules\Hr\Enums\RecruitmentRequestStatus;
use Modules\Hr\Enums\RecruitmentRequestType;
use Modules\Hr\Enums\RecruitmentScopeType;
use Modules\Hr\Enums\SalaryPer;
use Modules\Hr\Enums\WorkflowStepKey;
use Modules\Hr\Models\AnnualLeaveRule;
use Modules\Hr\Models\ApplicationForm;
use Modules\Hr\Models\ApplicationFormVersion;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\ContractType;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\HiringCampaign;
use Modules\Hr\Models\JobLevel;
use Modules\Hr\Models\JobPost;
use Modules\Hr\Models\JobPostVersion;
use Modules\Hr\Models\LeaveType;
use Modules\Hr\Models\Position;
use Modules\Hr\Models\PublicRecruitmentLink;
use Modules\Hr\Models\RecruitmentApplicant;
use Modules\Hr\Models\RecruitmentApplication;
use Modules\Hr\Models\RecruitmentHiringConversion;
use Modules\Hr\Models\RecruitmentRequest;
use Modules\Hr\Models\RecruitmentWorkflowDefinition;
use Modules\Hr\Models\RecruitmentWorkflowInstanceStep;
use Modules\Hr\Models\RecruitmentWorkflowStep;
use Modules\Hr\Models\Vacancy;
use Modules\Hr\Support\Recruitment\Access;
use Modules\Hr\Support\Recruitment\RecruitmentBootstrap;
use Modules\Hr\Support\Recruitment\RecruitmentConfig;

/*
| Task 7.10e: the recruitment scenario, request to hire, as hc-hr runs it.
| One manager asks for a seat, HR Syria approves it, the vacancy opens, an
| applicant is offered a trial contract, accepts, and is hired.
*/

beforeEach(function () {
    $this->travelTo('2026-11-24 10:00:00');

    // Both load once per PHP process, but every test has a fresh application:
    // load the recruitment settings, bindings and rate limiters into this one
    // when an earlier test already used up the once.
    if (config('recruitment') === null) {
        (new ReflectionProperty(RecruitmentConfig::class, 'loaded'))->setValue(null, false);
    }
    if (! app()->bound('hr.recruitment.leave-balances')) {
        (new ReflectionProperty(RecruitmentBootstrap::class, 'booted'))->setValue(null, false);
        RecruitmentBootstrap::boot();
    }

    foreach ([
        'recruitment-requests.view', 'recruitment-requests.manage', 'recruitment-requests.submit',
        'recruitment-requests.submit-without-manager', 'employees.create', 'contracts.create',
    ] as $name) {
        Permission::findOrCreate($name, 'web');
    }
    Role::findOrCreate('manager', 'web')->givePermissionTo('recruitment-requests.view', 'recruitment-requests.manage', 'recruitment-requests.submit');

    // A trial volunteer contract: category "trial" comes from id 3.
    $this->trial = ContractType::query()->find(3)
        ?? ContractType::factory()->create(['id' => 3, 'name_en' => 'Trial Volunteer Contract']);
    $this->trial->forceFill(['name_en' => 'Trial Volunteer Contract'])->save();

    $this->city = City::factory()->create();
    $program = Program::factory()->unassigned()->create();
    $position = fn (array $attributes): Position => Position::factory()->create([
        'program_id' => $program->id,
        'city_id' => $this->city->id,
        'is_active' => true,
        ...$attributes,
    ]);

    // The requester: a grade-5 manager at the top of his own tree.
    $this->managerPosition = $position(['job_level_id' => JobLevel::factory()->create(['grade' => 5])->id, 'max_emp' => 1, 'exist_emp' => 1]);
    $this->target = $position(['direct_manager_id' => $this->managerPosition->id, 'max_emp' => 1, 'exist_emp' => 0]);

    $managerEmployee = Employee::factory()->create(['is_active' => true]);
    Contract::factory()->create([
        'employee_id' => $managerEmployee->id,
        'position_id' => $this->managerPosition->id,
        'contract_type_id' => $this->trial->id,
        'is_active' => true,
    ]);
    $this->requester = User::factory()->role('manager')->create(['employee_id' => $managerEmployee->id]);
    $this->requester->givePermissionTo('recruitment-requests.submit-without-manager');

    // HR Syria: the governorate's reviewer (the default), an active employee.
    $this->hr = User::factory()->role('HR-SYR')->create(['employee_id' => Employee::factory()->create(['is_active' => true])->id]);
    $this->hr->givePermissionTo('employees.create', 'contracts.create');

    $definition = RecruitmentWorkflowDefinition::query()->forceCreate([
        'name' => 'Fill vacancy',
        'request_type' => RecruitmentRequestType::FillVacancy->value,
        'is_active' => true,
        'created_by_user_id' => $this->hr->id,
    ]);
    $version = $definition->versions()->forceCreate([
        'version_no' => 1,
        'rules_json' => [],
        'is_active' => true,
        'published_at' => now(),
    ]);
    RecruitmentWorkflowStep::query()->forceCreate([
        'definition_version_id' => $version->id,
        'step_order' => 1,
        'step_key' => WorkflowStepKey::GovernorateHrApproval->value,
        'role_key' => 'HR',
        'scope_rule' => RecruitmentScopeType::City->value,
        'required_condition_json' => ['modes' => [RecruitmentRequestMode::Normal->value]],
    ]);

    Access::flush();

    $this->draft = fn (): RecruitmentRequest => app(RecruitmentRequestDraftService::class)->create($this->requester, [
        'request_type' => RecruitmentRequestType::FillVacancy->value,
        'position_id' => $this->target->id,
        'recruitment_reason' => 'operational_need',
        'priority' => 'normal',
        'volunteer_type' => 'onsite',
        'weekly_hours' => 20,
        'needed_by_date' => '2026-12-01',
        'volunteer_period_start_date' => '2026-12-01',
        'volunteer_period_end_date' => '2027-02-28',
        'salary_amount' => 100,
        'currency' => Currency::USD->value,
        'salary_per' => SalaryPer::Month->value,
        'contract_type_id' => $this->trial->id,
        'detailed_explanation' => 'The seat is empty.',
        'volunteers_required' => 1,
        'vacancy_id' => null,
    ]);

    $this->currentStep = fn (RecruitmentRequest $request): RecruitmentWorkflowInstanceStep => RecruitmentWorkflowInstanceStep::query()
        ->whereHas('workflowInstance', fn ($query) => $query->where('request_id', $request->id))
        ->firstOrFail();

    $this->approvedVacancy = function (): Vacancy {
        $request = app(RecruitmentRequestSubmissionService::class)->submit($this->requester, ($this->draft)());
        app(RecruitmentApprovalService::class)->approve($this->hr, ($this->currentStep)($request), 1);

        return Vacancy::query()->where('created_from_request_id', $request->id)->firstOrFail();
    };

    // A published campaign for the vacancy, the way the HR screens leave it.
    $this->campaign = function (Vacancy $vacancy, string $linkMode = HiringCampaign::PUBLIC_OPEN): array {
        $campaign = HiringCampaign::query()->forceCreate([
            'vacancy_id' => $vacancy->id,
            'status' => HiringCampaignStatus::Open->value,
            'link_mode' => $linkMode,
            'created_by_user_id' => $this->hr->id,
        ]);
        $post = JobPost::query()->forceCreate(['campaign_id' => $campaign->id, 'status' => 'published', 'created_by_user_id' => $this->hr->id]);
        $postVersion = JobPostVersion::query()->forceCreate([
            'job_post_id' => $post->id, 'version_no' => 1, 'title_en' => 'Teacher', 'title_ar' => 'مدرس',
            'public_payload_json' => [], 'status' => 'published', 'published_at' => now(),
        ]);
        $post->forceFill(['current_version_id' => $postVersion->id])->save();
        $form = ApplicationForm::query()->forceCreate(['campaign_id' => $campaign->id, 'status' => 'published', 'created_by_user_id' => $this->hr->id]);
        $formVersion = ApplicationFormVersion::query()->forceCreate([
            'application_form_id' => $form->id, 'version_no' => 1,
            'fields_json' => app(RecruitmentApplicationFormService::class)->defaultFields(),
            'status' => 'published', 'published_at' => now(),
        ]);
        $form->forceFill(['current_version_id' => $formVersion->id])->save();
        $token = str_repeat('a1', 32);
        $link = PublicRecruitmentLink::query()->forceCreate([
            'campaign_id' => $campaign->id, 'job_post_version_id' => $postVersion->id, 'form_version_id' => $formVersion->id,
            'token_hash' => hash('sha256', $token), 'status' => PublicRecruitmentLink::STATUS_ACTIVE,
            'expires_at' => now()->addMonth(), 'created_by_user_id' => $this->hr->id,
        ]);

        return [$campaign, $formVersion, $link, $token];
    };
});

test('a fill-vacancy request runs to a hire: approval, offer, acceptance, conversion', function () {
    $request = app(RecruitmentRequestSubmissionService::class)->submit($this->requester, ($this->draft)());
    $step = ($this->currentStep)($request);

    // The governorate HR the step was assigned to may decide it; nobody else.
    expect((int) $step->assigned_user_id)->toBe($this->hr->id)
        ->and(Gate::forUser($this->hr)->allows('approve', $step))->toBeTrue()
        ->and(Gate::forUser($this->requester)->allows('approve', $step))->toBeFalse();

    app(RecruitmentApprovalService::class)->approve($this->hr, $step, 1);
    $vacancy = Vacancy::query()->where('created_from_request_id', $request->id)->firstOrFail();

    [$campaign, $formVersion, $link] = ($this->campaign)($vacancy);
    $applicant = RecruitmentApplicant::query()->forceCreate([
        'full_name' => 'سارة الأحمد', 'email_normalized' => 'sara@example.com', 'phone_normalized' => '0912345678',
        'city_id' => $this->city->id, 'source' => 'public',
    ]);
    $application = RecruitmentApplication::query()->forceCreate([
        'applicant_id' => $applicant->id, 'campaign_id' => $campaign->id, 'vacancy_id' => $vacancy->id,
        'form_version_id' => $formVersion->id, 'public_link_id' => $link->id, 'email_normalized' => 'sara@example.com',
        'status' => RecruitmentApplicationStatus::Selected, 'submitted_at' => now(),
    ]);
    $names = [
        'first_name_ar' => 'سارة', 'last_name_ar' => 'الأحمد', 'father_name_ar' => 'محمد', 'mother_name_ar' => 'ليلى',
        'first_name_en' => 'Sara', 'last_name_en' => 'Ahmad', 'father_name_en' => 'Mohammad', 'mother_name_en' => 'Layla',
    ];
    foreach ($names as $key => $value) {
        $application->answers()->forceCreate(['form_version_id' => $formVersion->id, 'field_key' => $key, 'answer_json' => ['value' => $value]]);
    }

    $offers = app(RecruitmentJobOfferService::class);
    $offer = $offers->create($this->hr, $application, [
        'contractTypeId' => $this->trial->id, 'salary' => '100', 'currency' => Currency::USD->value,
        'salaryPer' => SalaryPer::Month->value, 'startDate' => '2026-12-01', 'endDate' => '2027-02-28',
        'probationDays' => 0, 'weeklyHours' => 20, 'employmentType' => EmploymentType::PartTime->value,
        'place' => ContractPlace::Onsite->value, 'notes' => 'Trial period first.',
    ]);
    $offer = $offers->send($this->hr, $offer, (int) $offer->current_version_id, null);
    $offers->recordResponse($this->hr, $offer, (int) $offer->current_version_id, 'accepted', null);

    // The hire's leave balances need the annual rule and type on file.
    AnnualLeaveRule::query()->forceCreate(['days' => 14, 'day_per_month' => 1.17, 'years_of_volunteering' => 0]);
    LeaveType::query()->find(LeaveTypeCases::Annual) ?? LeaveType::factory()->create(['id' => LeaveTypeCases::Annual]);

    $conversions = app(RecruitmentHiringConversionService::class);
    $conversion = RecruitmentHiringConversion::query()->where('job_offer_id', $offer->id)->firstOrFail();

    // The hire form opens with the eight names the applicant already gave.
    $prefill = $conversions->prefill($conversion);
    expect($prefill)->toMatchArray([
        'firstNameAr' => 'سارة', 'lastNameAr' => 'الأحمد', 'fatherNameAr' => 'محمد', 'motherNameAr' => 'ليلى',
        'firstNameEn' => 'Sara', 'lastNameEn' => 'Ahmad', 'fatherNameEn' => 'Mohammad', 'motherNameEn' => 'Layla',
    ]);

    $conversions->convert($this->hr, $conversion, ['mode' => 'create', 'employee' => [
        ...collect($prefill)->only(['firstNameEn', 'lastNameEn', 'firstNameAr', 'lastNameAr', 'fatherNameEn', 'fatherNameAr', 'motherNameEn', 'motherNameAr'])->all(),
        'nationality' => Nationality::Syrian->value, 'nationalNumber' => '01234567890',
        'gender' => Gender::Female->value, 'maritalStatus' => MaritalStatus::Single->value,
        'username' => 'sara.ahmad',
    ]]);

    $contract = Contract::query()->where('position_id', $this->target->id)->sole();

    expect($conversion->fresh()->status)->toBe(RecruitmentHiringConversion::STATUS_CONVERTED)
        ->and(Employee::query()->whereKey($contract->employee_id)->value('first_name_en'))->toBe('Sara')
        ->and($application->fresh()->status)->toBe(RecruitmentApplicationStatus::Hired)
        // The hire takes the seat, and a trial is evaluated, never renewed.
        ->and((int) $this->target->fresh()->exist_emp)->toBe(1)
        ->and((bool) $contract->auto_renew)->toBeFalse();
});

test('a submitted request is re-checked against the requester tree', function () {
    $draft = ($this->draft)();

    // The post moved out from under the requester after the draft was saved.
    $this->target->forceFill(['direct_manager_id' => null])->save();

    expect(fn () => app(RecruitmentRequestSubmissionService::class)->submit($this->requester, $draft))
        ->toThrow(ValidationException::class);

    expect((int) $draft->fresh()->status)->toBe(RecruitmentRequestStatus::Draft->value);
});

test('the form asks for the eight names, each in its own script, before the email', function () {
    $service = app(RecruitmentApplicationFormService::class);
    $keys = array_column($service->defaultFields(), 'key');

    expect(array_slice($keys, array_search('first_name_ar', $keys, true), 9))
        ->toBe([...RecruitmentApplicationFormService::NAME_FIELDS, 'email'])
        ->and($keys)->not->toContain('full_name');

    $version = (new ApplicationFormVersion)->forceFill(['status' => 'published', 'fields_json' => $service->defaultFields()]);
    $answers = [
        'first_name_ar' => 'Sara', 'last_name_ar' => 'الأحمد', 'father_name_ar' => 'محمد', 'mother_name_ar' => 'ليلى',
        'first_name_en' => 'سارة', 'last_name_en' => 'Ahmad', 'father_name_en' => 'Mohammad', 'mother_name_en' => 'Layla',
    ];

    try {
        $service->validateSubmission($version, $answers);
        $this->fail('Names in the wrong script were accepted.');
    } catch (ValidationException $exception) {
        expect($exception->errors()['answers.first_name_ar'] ?? [])->toBe([__('hr_recruitment.careers.errors.name_arabic')])
            ->and($exception->errors()['answers.first_name_en'] ?? [])->toBe([__('hr_recruitment.careers.errors.name_latin')])
            ->and($exception->errors())->not->toHaveKey('answers.last_name_ar');
    }

    expect(RecruitmentApplicationFormService::fullNameFrom($answers))->toBe('Sara الأحمد');
});

test('an application made before the names were asked is prefilled from its full name', function () {
    $conversion = (new RecruitmentHiringConversion)->forceFill(['employee_payload_json' => ['full_name' => 'سارة الأحمد', 'email' => 'sara@example.com']]);

    expect(app(RecruitmentHiringConversionService::class)->prefill($conversion))->toMatchArray([
        'firstNameAr' => 'سارة', 'lastNameAr' => 'الأحمد', 'firstNameEn' => '', 'personalEmail' => 'sara@example.com',
    ]);
});

test('the invitation check answers in words, and sign-in opens the login in the vacancy language', function () {
    [, , , $token] = ($this->campaign)(($this->approvedVacancy)(), HiringCampaign::INVITE_ONLY);

    // The page body itself is not asserted: Blaze compiles the careers layout
    // (it sits under components/), so the page renders empty in this app.
    $this->post(route('hr.careers.invitation', ['token' => $token]), ['invitee_email' => 'not-an-email', 'locale' => 'ar'])
        ->assertStatus(422)
        ->assertViewHas('invitation', ['state' => 'check', 'error' => 'أدخل بريداً إلكترونياً صحيحاً.']);

    $this->post(route('hr.careers.invitation', ['token' => $token]), ['invitee_email' => 'stranger@example.com'])
        ->assertStatus(422)
        ->assertViewHas('invitation', ['state' => 'check', 'error' => __('hr_recruitment.careers.invitation.not_listed', [], 'en')]);

    $this->get(route('hr.careers.sign-in', ['token' => $token, 'lang' => 'ar']))
        ->assertRedirect(route('login'))
        ->assertSessionHas(SetLocale::SessionKey, 'ar');
});
