<?php

namespace Tests\Feature\Recruitment;

use App\Enums\ContractPlace;
use App\Enums\ContractType as EmploymentType;
use App\Enums\Currency;
use App\Enums\HiringCampaignStatus;
use App\Enums\LeaveTypeCases;
use App\Enums\Nationality;
use App\Enums\PayMode;
use App\Enums\RecruitmentApplicationStatus as AppStatus;
use App\Enums\RecruitmentEventType;
use App\Enums\RecruitmentJobOfferStatus as OfferStatus;
use App\Enums\SalaryPer;
use App\Enums\SessionDuration;
use App\Livewire\Recruitment\Applicants\ApplicationOffer;
use App\Livewire\Recruitment\Offers\Convert;
use App\Livewire\Recruitment\Offers\Show;
use App\Models\AnnualLeaveRule;
use App\Models\City;
use App\Models\Contract;
use App\Models\ContractType;
use App\Models\Employee;
use App\Models\HiringCampaign;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\RecruitmentApplication;
use App\Models\RecruitmentApplicationDecision;
use App\Models\RecruitmentHiringConversion as Conversion;
use App\Models\RecruitmentJobOffer as Offer;
use App\Models\Role;
use App\Models\User;
use App\Models\Vacancy;
use App\Notifications\NewEmployeeWelcome;
use App\Services\RecruitmentApplicationStatusService;
use App\Services\RecruitmentHiringConversionService as Conversions;
use App\Services\RecruitmentJobOfferService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use LogicException;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use ReflectionProperty;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\PermissionRegistrar;
use Tests\Concerns\BuildsRecruitmentApplications;
use Tests\Concerns\CreatesHrFixtures;
use Tests\TestCase;

/**
 * Phase 2 Task 10: an accepted offer becomes an employee and a contract.
 *
 * Each test names the spec section it covers: scope (نطاق التنفيذ /
 * المطلوب تنفيذه), rules (قواعد العمل), security (الأمان والصلاحيات), edge
 * (الحالات الاستثنائية), integrity (سلامة البيانات), acceptance (معايير
 * القبول), avoid (أخطاء يجب تجنبها).
 *
 * Runs as a web request (runningInConsole() off), so ContractObserver's own
 * capacity check is live exactly as in production. The clock is frozen on
 * 2026-10-05 09:00 (Europe/Istanbul).
 */
class RecruitmentHiringConversionTest extends TestCase
{
    use BuildsRecruitmentApplications, CreatesHrFixtures, RefreshDatabase;

    private const SALARY = '1500';

    /** The ids config('contracts.map_by_id') gives a category. */
    private const ANNUAL = 1;

    private const TRIAL = 3;

    private const SERVICE = 5;

    private City $homs;

    private User $hr;

    private HiringCampaign $campaign;

    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('en');

        $this->withoutMiddleware([LocaleSessionRedirect::class, LaravelLocalizationRedirectFilter::class]);
        Carbon::setTestNow(Carbon::parse('2026-10-05 09:00:00', 'Europe/Istanbul'));
        (new ReflectionProperty($this->app, 'isRunningInConsole'))->setValue($this->app, false);

        $this->homs = $this->makeCity('HMS', '002');
        $this->hr = $this->hr($this->homs);

        foreach ([self::ANNUAL => 'Volunteer contract', self::TRIAL => 'Trial contract', self::SERVICE => 'Service contract'] as $id => $name) {
            ContractType::query()->forceCreate(['id' => $id, 'name_en' => $name, 'name_ar' => $name]);
        }

        AnnualLeaveRule::query()->create(['days' => 14, 'day_per_month' => 1.5, 'years_of_volunteering' => 0]);
        foreach ([LeaveTypeCases::Annual, LeaveTypeCases::FullSick, LeaveTypeCases::HalfSick, LeaveTypeCases::Unpaid] as $id) {
            LeaveType::query()->forceCreate(['id' => $id, 'name_en' => "Leave {$id}", 'name_ar' => "إجازة {$id}", 'base_days' => 12]);
        }

        $this->campaign = $this->makeCampaignIn($this->homs);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    // ------------------------------------------------------------------
    // The hire
    // ------------------------------------------------------------------

    /** acceptance + scope: the accepted candidate becomes an employee with a contract, from the hire page, and everything around it follows. */
    public function test_hr_hires_the_accepted_candidate_as_a_new_employee_with_a_contract(): void
    {
        Notification::fake();
        [$application, $offer, $conversion] = $this->accepted();
        $position = $offer->position;
        $employeesBefore = Employee::query()->count();
        $this->actingAs($this->hr);

        Livewire::test(Convert::class, ['offerId' => $offer->id])
            ->assertSet('mode', Conversions::MODE_CREATE)
            ->set('employee', $this->person())
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('recruitment.offers.show', ['offerId' => $offer->id]));

        // The employee, in the HR system.
        $this->assertSame($employeesBefore + 1, Employee::query()->count());
        $employee = Employee::query()->where('username', 'candidate.one')->sole();
        $this->assertSame(['Candidate', 'One', 'مرشح', 'واحد'], [$employee->first_name_en, $employee->last_name_en, $employee->first_name_ar, $employee->last_name_ar]);
        $this->assertSame([$this->homs->id, '01234567890', '0944000000', true], [$employee->city_id, $employee->national_number, $employee->personal_phone, $employee->is_active]);

        // The contract, on the position, from the accepted terms.
        $contract = Contract::query()->where('employee_id', $employee->id)->sole();
        $this->assertSame([$position->id, self::ANNUAL, EmploymentType::FullTime, ContractPlace::Onsite], [$contract->position_id, $contract->contract_type_id, $contract->type, $contract->place]);
        $this->assertSame(['2026-11-01', '2027-10-31', 40], [$contract->start_date->toDateString(), $contract->end_date->toDateString(), $contract->weekly_hours]);
        $this->assertSame([1500.0, Currency::USD, SalaryPer::Month, PayMode::Fixed, true], [(float) $contract->salary, $contract->currency, (int) $contract->salary_per, $contract->pay_mode, $contract->is_active]);
        $this->assertStringNotContainsString('1500', (string) DB::table('contracts')->where('id', $contract->id)->value('salary'), 'Encrypted at rest, like every contract.');
        $this->assertGreaterThan(0, LeaveBalance::query()->where('contract_id', $contract->id)->count(), 'Leave balances, as the contract page creates them.');
        $this->assertSame(1, $position->fresh()->exist_emp);

        // The vacancy needed one person: it is filled, and its campaign closed.
        $vacancy = $offer->vacancy->fresh();
        $this->assertSame([Vacancy::FILLED, 1], [$vacancy->status, $vacancy->filled_count]);
        $this->assertNotNull($vacancy->closed_at);
        $this->assertSame(HiringCampaignStatus::Closed, $this->campaign->fresh()->status);
        $this->assertSame($this->hr->id, $this->campaign->fresh()->closed_by_user_id);
        $this->assertTrue(DB::table('recruitment_request_events')
            ->where('event_type', RecruitmentEventType::CampaignStateChanged)
            ->where('meta->campaign_id', $this->campaign->id)
            ->where('meta->reason', Conversions::CAMPAIGN_CLOSE_REASON)
            ->exists());

        // The application, the applicant and the conversion.
        $this->assertSame(AppStatus::Hired, $application->fresh()->status);
        $this->assertSame($employee->id, $application->applicant->fresh()->employee_id);
        $conversion->refresh();
        $this->assertSame([Conversion::STATUS_CONVERTED, $employee->id, $contract->id, $this->hr->id, 1, null],
            [$conversion->status, $conversion->employee_id, $conversion->contract_id, $conversion->converted_by_user_id, $conversion->attempt_count, $conversion->failure_reason]);
        $this->assertNotNull($conversion->converted_at);
        $this->assertSame(OfferStatus::Accepted, $offer->fresh()->status);

        // Colleagues are welcomed, as for any first contract.
        Notification::assertSentTo($this->hr, NewEmployeeWelcome::class);
    }

    /** rules + avoid: the contract is built from the accepted version — never an earlier one, never the live offer. */
    public function test_the_contract_takes_the_accepted_snapshot(): void
    {
        $application = $this->selected();
        $offer = $this->offers()->send($this->hr, $offer = $this->createOffer($application, ['salary' => '1200']), $offer->current_version_id, null)->fresh();
        $offer = $this->offers()->revise($this->hr, $offer, $offer->current_version_id, $this->terms(['salary' => '1750', 'weeklyHours' => 30, 'place' => ContractPlace::Online]), 'Better terms after talking to the candidate.')->fresh();
        $offer = $this->offers()->send($this->hr, $offer, $offer->current_version_id, null)->fresh();
        $offer = $this->offers()->recordResponse($this->hr, $offer, $offer->current_version_id, OfferStatus::Accepted, null)->fresh();
        $conversion = $offer->hiringConversion;
        $this->assertSame($offer->current_version_id, $conversion->job_offer_version_id);

        $this->hire($conversion);

        $contract = Contract::query()->findOrFail($conversion->fresh()->contract_id);
        $this->assertSame([1750.0, 30, ContractPlace::Online], [(float) $contract->salary, $contract->weekly_hours, $contract->place]);

        // The accepted version is frozen: there is no live offer to drift from.
        $this->assertThrows(fn () => $offer->currentVersion->forceFill(['salary_amount' => '9'])->save(), LogicException::class);
    }

    /** rules: what the offer does not carry starts where the contract composer starts a new contract. */
    public function test_terms_the_offer_does_not_carry_take_the_composer_defaults(): void
    {
        // Annual: to the end of the year it starts in; 40 hours; on site; full time.
        $annual = $this->accepted('Annual One', ['endDate' => '', 'weeklyHours' => '', 'place' => '', 'employmentType' => ''])[2];
        $this->hire($annual, ['employee' => $this->person(['username' => 'annual.one', 'nationalNumber' => '01234567801'])]);
        $contract = $annual->fresh()->contract;
        $this->assertSame(['2026-11-01', '2026-12-31', 40, ContractPlace::Onsite, EmploymentType::FullTime],
            [$contract->start_date->toDateString(), $contract->end_date->toDateString(), $contract->weekly_hours, $contract->place, $contract->type]);
        $this->assertSame('09:00', substr((string) $contract->work_hours_from, 0, 5));

        // Trial: three months.
        $trial = $this->accepted('Trial One', ['contractTypeId' => self::TRIAL, 'endDate' => ''], $this->makeCampaignIn($this->homs))[2];
        $this->hire($trial, ['employee' => $this->person(['username' => 'trial.one', 'nationalNumber' => '01234567802'])]);
        $this->assertSame('2027-02-01', $trial->fresh()->contract->end_date->toDateString());

        // Service: freelance, and 0 hours is allowed — and no leave clause.
        $service = $this->accepted('Service One', ['contractTypeId' => self::SERVICE, 'employmentType' => '', 'weeklyHours' => ''], $this->makeCampaignIn($this->homs))[2];
        $this->hire($service, ['employee' => $this->person(['username' => 'service.one', 'nationalNumber' => '01234567803'])]);
        $contract = $service->fresh()->contract;
        $this->assertSame([EmploymentType::Freelance, 0], [$contract->type, $contract->weekly_hours]);
        $this->assertSame(0, LeaveBalance::query()->where('contract_id', $contract->id)->count());
    }

    /** scope: pay per session needs the session length the offer does not carry. */
    public function test_pay_per_session_needs_the_session_length(): void
    {
        [, $offer, $conversion] = $this->accepted('Session One', ['salaryPer' => SalaryPer::Session]);
        $this->actingAs($this->hr);

        Livewire::test(Convert::class, ['offerId' => $offer->id])
            ->assertSee(__('recruitment-applicant.conversions.session_duration'))
            ->set('employee', $this->person())
            ->call('save')
            ->assertHasErrors(['sessionDuration']);
        $this->assertSame(Conversion::STATUS_PENDING, $conversion->fresh()->status, 'A form mistake is not a failed attempt.');

        $this->hire($conversion, ['sessionDuration' => (string) SessionDuration::HourAndHalf]);
        $this->assertSame(SessionDuration::HourAndHalf, $conversion->fresh()->contract->session_duration);
    }

    // ------------------------------------------------------------------
    // Existing people
    // ------------------------------------------------------------------

    /** edge + avoid: a candidate with an existing employee's email or phone is never created twice silently. */
    public function test_an_existing_employee_with_the_same_email_or_phone_is_linked_not_duplicated(): void
    {
        [$application, $offer, $conversion] = $this->accepted();
        $existing = $this->makeEmployee($this->homs, 'already.here', ['personal_email' => strtoupper($application->email_normalized)]);
        $byPhone = $this->makeEmployee($this->homs, 'same.phone', ['work_phone' => '0944111222']);
        $application->applicant->forceFill(['phone_normalized' => '+963944111222'])->save();

        $matches = $this->conversions()->matches($conversion->fresh());
        $this->assertEqualsCanonicalizing([[$existing->id, ['email']], [$byPhone->id, ['phone']]],
            $matches->map(fn (array $match): array => [$match['employee']->id, $match['reasons']])->all());

        // Creating someone new needs HR to say it is a different person.
        $employees = Employee::query()->count();
        $this->assertValidationError(fn () => $this->hire($conversion), 'confirmNotDuplicate', 'possible_duplicate');
        $this->assertSame($employees, Employee::query()->count());
        $this->assertSame([Conversion::STATUS_PENDING, 0], [$conversion->fresh()->status, $conversion->fresh()->attempt_count]);

        // Linking: no new employee; the contract is theirs.
        $this->hire($conversion, ['mode' => Conversions::MODE_LINK, 'employeeId' => (string) $existing->id]);
        $this->assertSame($employees, Employee::query()->count());
        $conversion->refresh();
        $this->assertSame([$existing->id, $existing->id], [$conversion->employee_id, $conversion->contract->employee_id]);
        $this->assertSame(Conversions::MODE_LINK, $conversion->employee_payload_json['mode']);
        $this->assertSame($existing->id, $application->applicant->fresh()->employee_id);
    }

    /** edge: HR confirms the match is someone else, and a new employee is created. */
    public function test_confirming_a_different_person_creates_a_new_employee(): void
    {
        [$application, , $conversion] = $this->accepted();
        $this->makeEmployee($this->homs, 'namesake', ['personal_email' => $application->email_normalized]);

        $this->hire($conversion, ['confirmNotDuplicate' => true]);

        $this->assertSame('candidate.one', $conversion->fresh()->employee->username);
    }

    /** edge: an internal applicant is that employee; a national number on file is that employee. Both can only be linked. */
    public function test_an_internal_applicant_or_a_known_national_number_can_only_be_linked(): void
    {
        // Internal applicant: linking is preselected, creating is refused.
        [$application, $offer, $conversion] = $this->accepted();
        $staff = $this->makeEmployee($this->homs, 'staff.member');
        $other = $this->makeEmployee($this->homs, 'other.one', ['personal_email' => $application->email_normalized]);
        $application->applicant->forceFill(['employee_id' => $staff->id])->save();

        $this->actingAs($this->hr);
        Livewire::test(Convert::class, ['offerId' => $offer->id])
            ->assertSet('mode', Conversions::MODE_LINK)
            ->assertSet('employeeId', (string) $staff->id)
            ->assertSee(__('recruitment-applicant.conversions.match_reasons.internal'));

        $this->assertValidationError(fn () => $this->hire($conversion, ['confirmNotDuplicate' => true]), 'mode', 'internal_must_link');
        $this->assertValidationError(fn () => $this->hire($conversion, ['mode' => Conversions::MODE_LINK, 'employeeId' => (string) $other->id]), 'employeeId', 'internal_must_link');
        $this->hire($conversion, ['mode' => Conversions::MODE_LINK, 'employeeId' => (string) $staff->id]);
        $this->assertSame($staff->id, $conversion->fresh()->employee_id);

        // A national number already on file: that person, linked.
        $second = $this->accepted('Second One', [], $this->makeCampaignIn($this->homs))[2];
        $known = $this->makeEmployee($this->homs, 'known.person', ['national_number' => '09876543210']);
        $this->assertValidationError(fn () => $this->hire($second, ['employee' => $this->person(['nationalNumber' => '09876543210', 'username' => 'second.one'])]), 'employee.nationalNumber', 'national_number_taken');

        $this->hire($second, ['mode' => Conversions::MODE_LINK, 'employeeId' => (string) $known->id, 'employee' => ['nationalNumber' => '09876543210']]);
        $this->assertSame($known->id, $second->fresh()->employee_id);
    }

    /** edge: a former employee hired back is active again. */
    public function test_a_former_employee_hired_back_is_reactivated(): void
    {
        [$application, , $conversion] = $this->accepted();
        $former = $this->makeEmployee($this->homs, 'former.one', ['personal_email' => $application->email_normalized, 'is_active' => false]);

        $this->hire($conversion, ['mode' => Conversions::MODE_LINK, 'employeeId' => (string) $former->id]);

        $this->assertTrue($former->fresh()->is_active);
        $this->assertTrue(Activity::query()->where('description', 'recruitment_hiring_conversion.converted')->sole()->properties['employee_reactivated']);
    }

    /** edge + integrity: an employee already holding this position is renewed, not hired onto it a second time. */
    public function test_an_employee_already_on_the_position_is_not_hired_onto_it_again(): void
    {
        [$application, $offer, $conversion] = $this->accepted();
        $holder = $this->makeEmployee($this->homs, 'seat.holder', ['personal_email' => $application->email_normalized, 'work_phone' => '0944555666']);
        Contract::query()->create([
            'employee_id' => $holder->id, 'position_id' => $offer->position_id, 'contract_type_id' => self::ANNUAL,
            'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'currency' => Currency::USD, 'salary_per' => SalaryPer::Month,
            'salary' => 900, 'weekly_hours' => 40, 'is_active' => true,
        ]);
        $contracts = Contract::query()->count();

        $this->assertValidationError(fn () => $this->hire($conversion, ['mode' => Conversions::MODE_LINK, 'employeeId' => (string) $holder->id]), 'employeeId', 'already_on_position');
        $this->assertSame([$contracts, Conversion::STATUS_PENDING], [Contract::query()->count(), $conversion->fresh()->status]);

        // On another position they can be hired: a second post is a real hire.
        [$other, , $elsewhere] = $this->accepted('Other One', [], $this->makeCampaignIn($this->homs));
        $other->applicant->forceFill(['phone_normalized' => '+963944555666'])->save();
        $this->hire($elsewhere, ['mode' => Conversions::MODE_LINK, 'employeeId' => (string) $holder->id]);
        $this->assertSame($holder->id, $elsewhere->fresh()->employee_id);
    }

    /** security: only a matched employee can be linked, and only one in the city HR manages. */
    public function test_linking_is_limited_to_matched_employees_in_scope(): void
    {
        [$application, , $conversion] = $this->accepted();
        $stranger = $this->makeEmployee($this->homs, 'stranger');
        $elsewhere = $this->makeEmployee($this->makeCity('ALP', '001'), 'elsewhere', ['personal_email' => $application->email_normalized]);

        // A tampered id links nobody.
        $this->assertValidationError(fn () => $this->hire($conversion, ['mode' => Conversions::MODE_LINK, 'employeeId' => (string) $stranger->id]), 'employeeId', 'employee_not_matched');
        // Matched, but in another city.
        $this->assertValidationError(fn () => $this->hire($conversion, ['mode' => Conversions::MODE_LINK, 'employeeId' => (string) $elsewhere->id]), 'employeeId', 'employee_out_of_scope');

        $this->assertNull($conversion->fresh()->contract_id);
        $this->assertSame(0, Contract::query()->count());
    }

    // ------------------------------------------------------------------
    // Once, and all or nothing
    // ------------------------------------------------------------------

    /** integrity + acceptance + avoid: the conversion runs once; a retry after a timeout returns the same hire. */
    public function test_the_conversion_runs_once_and_a_retry_returns_the_same_hire(): void
    {
        [, $offer, $conversion] = $this->accepted();
        $first = $this->hire($conversion);

        $retry = $this->hire($conversion->fresh(), ['employee' => $this->person(['username' => 'someone.else', 'nationalNumber' => '01111111111'])]);

        $this->assertTrue($retry->is($first));
        $this->assertSame([1, 1, 1, 1], [
            Contract::query()->count(),
            Employee::query()->where('username', 'candidate.one')->count() + Employee::query()->where('username', 'someone.else')->count(),
            $offer->position->fresh()->exist_emp,
            $offer->vacancy->fresh()->filled_count,
        ]);
        $this->assertSame(1, $conversion->fresh()->attempt_count);
        $this->assertSame(1, Activity::query()->where('description', 'recruitment_hiring_conversion.converted')->count());

        // The hire page shows what was done instead of the form.
        $this->actingAs($this->hr);
        Livewire::test(Convert::class, ['offerId' => $offer->id])
            ->assertSee(__('recruitment-applicant.conversions.statuses.converted'))
            ->assertDontSee(__('recruitment-applicant.conversions.confirm_hint'));

        // A converted row is final, and nothing is deleted.
        $this->assertThrows(fn () => $conversion->fresh()->forceFill(['contract_id' => null])->save(), LogicException::class);
        $this->assertThrows(fn () => $conversion->fresh()->delete(), LogicException::class);
        $this->assertThrows(fn () => Conversion::query()->whereKey($conversion->id)->delete(), LogicException::class);

        // The database refuses a second conversion of the offer, or of the contract.
        $this->assertThrows(fn () => DB::table('recruitment_hiring_conversions')->insert([
            'job_offer_id' => $offer->id, 'job_offer_version_id' => $offer->current_version_id, 'applicant_id' => $conversion->applicant_id,
            'employee_payload_json' => '{}', 'status' => 'pending', 'started_at' => now(), 'converted_by_user_id' => $this->hr->id,
        ]), UniqueConstraintViolationException::class);
    }

    /** edge + integrity + avoid: the contract fails after the employee was created — nothing is left behind, and a retry works. */
    public function test_a_contract_that_fails_leaves_no_employee_behind(): void
    {
        [$application, $offer, $conversion] = $this->accepted();
        $employees = Employee::query()->count();
        $fail = true;
        Contract::creating(function () use (&$fail): void {
            if ($fail) {
                throw new RuntimeException('Simulated failure while saving the contract.');
            }
        });

        $this->actingAs($this->hr);
        Livewire::test(Convert::class, ['offerId' => $offer->id])
            ->set('employee', $this->person())
            ->call('save')
            ->assertHasErrors(['conversion']);

        // Rolled back as a whole.
        $this->assertSame($employees, Employee::query()->count());
        $this->assertSame(0, Contract::query()->count());
        $this->assertSame([0, 0], [$offer->position->fresh()->exist_emp, $offer->vacancy->fresh()->filled_count]);
        $this->assertSame(AppStatus::OfferAccepted, $application->fresh()->status);
        $this->assertNull($application->applicant->fresh()->employee_id);

        // …and recorded as a failed attempt.
        $this->assertSame([Conversion::STATUS_FAILED, 'unexpected', 1], [$conversion->fresh()->status, $conversion->fresh()->failure_reason, $conversion->fresh()->attempt_count]);
        $this->assertSame('unexpected', Activity::query()->where('description', 'recruitment_hiring_conversion.failed')->sole()->properties['reason']);

        $fail = false;
        $this->hire($conversion);
        $this->assertSame([Conversion::STATUS_CONVERTED, 2, null], [$conversion->fresh()->status, $conversion->fresh()->attempt_count, $conversion->fresh()->failure_reason]);
    }

    // ------------------------------------------------------------------
    // What stops a hire
    // ------------------------------------------------------------------

    /** edge: the offer was accepted but the vacancy has since been closed. */
    public function test_a_closed_vacancy_blocks_the_hire(): void
    {
        [, $offer, $conversion] = $this->accepted();
        $offer->vacancy->forceFill(['status' => Vacancy::CLOSED, 'closed_at' => now(), 'close_reason' => 'On hold'])->save();

        $this->assertBlocked($conversion, 'vacancy_closed');
        $this->assertSame(['vacancy_closed'], $this->conversions()->blockers($conversion->fresh()));

        $this->actingAs($this->hr);
        Livewire::test(Convert::class, ['offerId' => $offer->id])->assertSee(__('recruitment-applicant.conversions.errors.vacancy_closed'));
    }

    /** edge: the position was deactivated. */
    public function test_an_inactive_position_blocks_the_hire(): void
    {
        [, $offer, $conversion] = $this->accepted();
        $offer->position->forceFill(['is_active' => false])->save();

        $this->assertBlocked($conversion, 'position_inactive');
    }

    /** edge: the position is at capacity; once a seat is freed, the retry goes through. */
    public function test_a_full_position_blocks_the_hire_until_a_seat_is_freed(): void
    {
        [, $offer, $conversion] = $this->accepted();
        $offer->position->forceFill(['max_emp' => 1, 'exist_emp' => 1])->save();

        $this->assertBlocked($conversion, 'position_full');

        $offer->position->forceFill(['max_emp' => 2])->save();
        $this->hire($conversion);
        $this->assertSame([Conversion::STATUS_CONVERTED, 2], [$conversion->fresh()->status, $conversion->fresh()->attempt_count]);
        $this->assertSame(2, $offer->position->fresh()->exist_emp);
    }

    /** rules + avoid: a vacancy needing two people stays open after the first hire and is filled by the second — never closed early. */
    public function test_a_vacancy_needing_several_people_stays_open_until_every_seat_is_filled(): void
    {
        $vacancy = $this->campaign->vacancy;
        $vacancy->forceFill(['needed_count' => 2])->save();
        $first = $this->accepted('First One')[2];
        $second = $this->accepted('Second One')[2];

        $this->hire($first);
        $vacancy->refresh();
        $this->assertSame([Vacancy::OPEN, 1, null], [$vacancy->status, $vacancy->filled_count, $vacancy->closed_at]);
        $this->assertSame(HiringCampaignStatus::Open, $this->campaign->fresh()->status, 'Still recruiting for the second seat.');

        $this->hire($second, ['employee' => $this->person(['username' => 'second.one', 'nationalNumber' => '01234567899'])]);
        $vacancy->refresh();
        $this->assertSame([Vacancy::FILLED, 2], [$vacancy->status, $vacancy->filled_count]);
        $this->assertSame(HiringCampaignStatus::Closed, $this->campaign->fresh()->status);
    }

    /** edge: the vacancy filled up (its need was cut) before this accepted offer was converted. */
    public function test_a_vacancy_already_filled_blocks_the_hire(): void
    {
        $vacancy = $this->campaign->vacancy;
        $vacancy->forceFill(['needed_count' => 2])->save();
        $first = $this->accepted('First One')[2];
        $second = $this->accepted('Second One')[2];
        $vacancy->forceFill(['needed_count' => 1])->save();

        $this->hire($first);
        $this->assertBlocked($second, 'vacancy_full');
    }

    /** avoid: only the accepted version of an accepted offer is converted. */
    public function test_only_the_accepted_version_of_an_accepted_offer_is_converted(): void
    {
        // A conversion row pointing at an offer still with the candidate.
        $sent = $this->offers()->send($this->hr, $offer = $this->createOffer($this->selected()), $offer->current_version_id, null)->fresh();
        $forged = Conversion::query()->forceCreate([
            'job_offer_id' => $sent->id, 'job_offer_version_id' => $sent->current_version_id, 'applicant_id' => $sent->application->applicant_id,
            'employee_payload_json' => ['full_name' => 'Candidate One'], 'status' => Conversion::STATUS_PENDING, 'started_at' => now(), 'converted_by_user_id' => $this->hr->id,
        ]);
        $this->assertBlocked($forged, 'offer_not_accepted');

        // No conversion at all (a draft offer): no hire page.
        $draft = $this->createOffer($this->selected('Draft One'));
        $this->actingAs($this->hr);
        Livewire::test(Convert::class, ['offerId' => $draft->id])->assertNotFound();
    }

    /** avoid: an offer cannot promise terms the contract cannot hold, and an old one that does is refused, not bent. */
    public function test_offer_terms_that_cannot_be_a_contract_are_refused(): void
    {
        $application = $this->selected();
        $this->assertValidationError(fn () => $this->createOffer($application, ['endDate' => '2027-11-02']), 'endDate', 'end_date_too_far', 'offers');
        $this->assertValidationError(fn () => $this->createOffer($application, ['employmentType' => EmploymentType::Freelance]), 'employmentType', 'employment_type_mismatch', 'offers');
        $this->assertValidationError(fn () => $this->createOffer($application, ['contractTypeId' => self::SERVICE, 'employmentType' => EmploymentType::FullTime]), 'employmentType', 'employment_type_mismatch', 'offers');

        // One accepted before the rule: blocked at the hire, never silently shortened.
        [, $offer, $conversion] = $this->accepted('Legacy One', [], $this->makeCampaignIn($this->homs));
        DB::table('recruitment_job_offer_versions')->where('id', $offer->current_version_id)->update(['end_date' => '2028-11-01']);
        $this->assertBlocked($conversion, 'contract_term_invalid');
    }

    // ------------------------------------------------------------------
    // Security, audit, status
    // ------------------------------------------------------------------

    /** security: hiring needs its own permission, contracts.create, sight of pay, and the offer in scope. */
    public function test_hiring_needs_its_own_permission_pay_visibility_and_scope(): void
    {
        [, $offer, $conversion] = $this->accepted();
        $url = route('recruitment.offers.convert', ['offerId' => $offer->id]);

        // Nobody without recruitment rights.
        $employee = $this->makeUser($this->homs, 'employee');
        $this->actingAs($employee)->get($url)->assertForbidden();
        $this->assertThrows(fn () => $this->conversions()->convert($employee, $conversion, $this->input()), AuthorizationException::class);

        // super-admin holds every permission but is salary-blind: it cannot put a salary into a contract.
        $admin = $this->makeUser($this->homs, 'super-admin');
        $this->assertTrue($admin->hasActivePermission('recruitment-applicants.convert-hires'));
        $this->assertFalse($admin->can('convert', $conversion));
        $this->assertThrows(fn () => $this->conversions()->convert($admin, $conversion, $this->input()), AuthorizationException::class);

        // HR of another city does not even see the offer.
        $otherHr = $this->hr($this->makeCity('DMS', '003'));
        $this->assertFalse($otherHr->can('convert', $conversion));
        $this->actingAs($otherHr);
        Livewire::test(Convert::class, ['offerId' => $offer->id])->assertNotFound();

        // HR without contracts.create, then without the permission itself.
        $noContracts = $this->without($this->hr, ['contracts.create']);
        $this->assertThrows(fn () => $this->conversions()->convert($noContracts, $conversion, $this->input()), AuthorizationException::class);
        $noConvert = $this->without($this->hr, ['recruitment-applicants.convert-hires']);
        $this->actingAs($noConvert)->get($url)->assertForbidden();
        $this->assertThrows(fn () => $this->conversions()->convert($noConvert, $conversion, $this->input()), AuthorizationException::class);

        // A refusal records nothing and creates nothing.
        $this->assertSame([Conversion::STATUS_PENDING, 0, 0], [$conversion->fresh()->status, $conversion->fresh()->attempt_count, Contract::query()->count()]);
    }

    /** security: the new employee is validated like the HR employee form; a form mistake is not a failed attempt. */
    public function test_the_new_employee_is_validated_like_the_hr_employee_form(): void
    {
        [, , $conversion] = $this->accepted();
        $this->makeEmployee($this->homs, 'taken.name');

        $errors = $this->assertValidationError(fn () => $this->hire($conversion, ['employee' => array_fill_keys(array_keys($this->person()), '')]), 'employee.firstNameEn');
        foreach (['firstNameAr', 'lastNameEn', 'lastNameAr', 'fatherNameEn', 'fatherNameAr', 'motherNameEn', 'motherNameAr', 'nationality', 'nationalNumber', 'gender', 'maritalStatus', 'username'] as $key) {
            $this->assertArrayHasKey('employee.'.$key, $errors);
        }

        foreach ([
            'nationalNumber' => ['nationalNumber' => '123'],
            'username' => ['username' => 'Has Spaces'],
            'personalPhone' => ['personalPhone' => '944000000'],
            'personalEmail' => ['personalEmail' => 'not-an-email'],
            'dob' => ['dob' => '2030-01-01'],
        ] as $field => $bad) {
            $this->assertValidationError(fn () => $this->hire($conversion, ['employee' => $this->person($bad)]), 'employee.'.$field);
        }

        $this->assertValidationError(fn () => $this->hire($conversion, ['employee' => $this->person(['username' => 'taken.name'])]), 'employee.username', 'username_taken');
        $this->assertValidationError(fn () => $this->hire($conversion, ['mode' => 'merge']), 'mode', 'mode_required');

        $this->assertSame([Conversion::STATUS_PENDING, 0, 1], [$conversion->fresh()->status, $conversion->fresh()->attempt_count, Employee::query()->where('username', 'taken.name')->count()]);

        // A foreign national number is not held to the Syrian 11 digits.
        $this->hire($conversion, ['employee' => $this->person(['nationality' => (string) Nationality::Lebanese, 'nationalNumber' => 'LB-778899'])]);
        $this->assertSame('LB-778899', $conversion->fresh()->employee->national_number);
    }

    /** security: the audit names the hire and each failed attempt — never the salary. */
    public function test_the_audit_records_the_hire_without_the_salary(): void
    {
        [$application, $offer, $conversion] = $this->accepted();
        $offer->position->forceFill(['max_emp' => 0])->save();
        $this->assertBlocked($conversion, 'position_full');
        $offer->position->forceFill(['max_emp' => 2])->save();
        $this->hire($conversion);

        $entries = Activity::query()->where('log_name', Conversions::AUDIT_LOG)->orderBy('id')->get();
        $this->assertSame(['recruitment_hiring_conversion.failed', 'recruitment_hiring_conversion.converted'], $entries->pluck('description')->all());

        $converted = $entries->last();
        $this->assertTrue($converted->causer->is($this->hr));
        $this->assertSame([$offer->id, $application->id, Conversions::MODE_CREATE, $conversion->fresh()->employee_id, $conversion->fresh()->contract_id, Vacancy::FILLED, [$this->campaign->id]],
            [$converted->properties['job_offer_id'], $converted->properties['application_id'], $converted->properties['mode'], $converted->properties['employee_id'], $converted->properties['contract_id'], $converted->properties['vacancy_status'], $converted->properties['campaigns_closed']]);

        foreach ($entries as $entry) {
            $this->assertStringNotContainsString('1500', json_encode($entry->properties));
        }
    }

    /** acceptance: "hired" is set only by the conversion, never from the decision panel. */
    public function test_hired_is_set_only_by_the_conversion(): void
    {
        [$application, , $conversion] = $this->accepted();
        $statuses = app(RecruitmentApplicationStatusService::class);

        $this->assertSame([], $statuses->targets($application->fresh()));
        $this->assertThrows(fn () => $statuses->change($this->hr, $application->fresh(), AppStatus::OfferAccepted, AppStatus::Hired, null), ValidationException::class);

        $this->hire($conversion);
        $decision = RecruitmentApplicationDecision::query()->where('application_id', $application->id)->latest('id')->first();
        $this->assertSame([AppStatus::OfferAccepted, AppStatus::Hired, RecruitmentApplicationStatusService::REASON_CONVERSION],
            [$decision->from_status, $decision->to_status, $decision->reason_code]);
    }

    /** acceptance: the offer page and the applicant page follow the hire: waiting, failed, hired. */
    public function test_the_offer_and_applicant_pages_follow_the_hire(): void
    {
        [$application, $offer, $conversion] = $this->accepted();
        // A second campaign on the same seat, published and not yet open.
        $scheduled = HiringCampaign::query()->forceCreate([
            'vacancy_id' => $offer->vacancy_id, 'status' => HiringCampaignStatus::Published, 'link_mode' => HiringCampaign::INVITE_ONLY,
            'starts_at' => now()->addWeek(), 'ends_at' => now()->addMonth(), 'created_by_user_id' => $this->campaign->created_by_user_id,
        ]);
        $this->actingAs($this->hr);

        Livewire::test(Show::class, ['offerId' => $offer->id])
            ->assertSee(__('recruitment-applicant.conversions.statuses.pending'))
            ->assertSee(__('recruitment-applicant.conversions.open'));
        $this->get(route('recruitment.offers.convert', ['offerId' => $offer->id]))
            ->assertOk()->assertSee(__('recruitment-applicant.conversions.title'))->assertSee('1,500.00');

        $offer->position->forceFill(['max_emp' => 0])->save();
        $this->assertBlocked($conversion, 'position_full');
        Livewire::test(Show::class, ['offerId' => $offer->id])
            ->assertSee(__('recruitment-applicant.conversions.statuses.failed'))
            ->assertSee(__('recruitment-applicant.conversions.errors.position_full'));

        $offer->position->forceFill(['max_emp' => 2])->save();
        $this->hire($conversion);
        $conversion->refresh();
        Livewire::test(Show::class, ['offerId' => $offer->id])
            ->assertSee(__('recruitment-applicant.conversions.statuses.converted'))
            ->assertSee(route('contracts.show', $conversion->contract_id))
            ->assertSee(route('employees.show', 'candidate.one'))
            ->assertSee(__('recruitment-applicant.conversions.campaigns_still_open', ['ids' => '#'.$scheduled->id]))
            ->assertDontSee(__('recruitment-applicant.conversions.open'));
        $this->assertSame(HiringCampaignStatus::Published, $scheduled->fresh()->status, 'Not ours to decide: HR is told instead.');
        Livewire::test(ApplicationOffer::class, ['applicationId' => $application->id])->assertSee(__('recruitment-applicant.statuses.hired'));

        // Someone who may follow offers but not hire sees the status, not the button.
        $viewer = $this->without($this->hr, ['recruitment-applicants.convert-hires']);
        $pending = $this->accepted('Pending One', [], $this->makeCampaignIn($this->homs))[1];
        $this->actingAs($viewer);
        Livewire::test(Show::class, ['offerId' => $pending->id])
            ->assertSee(__('recruitment-applicant.conversions.statuses.pending'))
            ->assertDontSee(__('recruitment-applicant.conversions.open'));
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function hr(City $city): User
    {
        return $this->makeUser($city, 'HR-'.$city->slug, [
            'recruitment-campaigns.view',
            'recruitment-campaigns.manage',
            'contracts.create',
            'employees.create',
            'employees.update',
            ...array_map(
                fn (string $action): string => "recruitment-applicants.{$action}",
                config('access-control.groups.recruitment-applicants'),
            ),
        ]);
    }

    private function without(User $user, array $permissions): User
    {
        Role::findByName($user->myRole(), 'web')->revokePermissionTo($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user->fresh();
    }

    private function offers(): RecruitmentJobOfferService
    {
        return app(RecruitmentJobOfferService::class);
    }

    private function conversions(): Conversions
    {
        return app(Conversions::class);
    }

    private function selected(string $name = 'Candidate One', ?HiringCampaign $campaign = null): RecruitmentApplication
    {
        // This helper starts after a completed interview; interview coverage
        // owns the earlier recruitment stages.
        $application = $this->applyTo($campaign ?? $this->campaign, $this->makeApplicant($name), ['status' => AppStatus::Interviewed]);
        $statuses = app(RecruitmentApplicationStatusService::class);
        $statuses->change($this->hr, $application, AppStatus::Interviewed, AppStatus::Selected, null);

        return $application->fresh();
    }

    private function terms(array $overrides = []): array
    {
        return array_merge([
            'contractTypeId' => self::ANNUAL,
            'salary' => self::SALARY,
            'currency' => Currency::USD,
            'salaryPer' => SalaryPer::Month,
            'startDate' => '2026-11-01',
            'endDate' => '2027-10-31',
            'weeklyHours' => 40,
            'probationDays' => 90,
            'employmentType' => EmploymentType::FullTime,
            'place' => ContractPlace::Onsite,
            'notes' => '',
        ], $overrides);
    }

    private function createOffer(RecruitmentApplication $application, array $overrides = []): Offer
    {
        return $this->offers()->create($this->hr, $application, $this->terms($overrides))->fresh();
    }

    /**
     * A selected candidate whose offer was sent and accepted — the pending
     * conversion Task 9 hands over.
     *
     * @return array{0: RecruitmentApplication, 1: Offer, 2: Conversion}
     */
    private function accepted(string $name = 'Candidate One', array $terms = [], ?HiringCampaign $campaign = null): array
    {
        $application = $this->selected($name, $campaign);
        $offer = $this->createOffer($application, $terms);
        $offer = $this->offers()->send($this->hr, $offer, $offer->current_version_id, null)->fresh();
        $offer = $this->offers()->recordResponse($this->hr, $offer, $offer->current_version_id, OfferStatus::Accepted, 'Accepted by phone.')->fresh();

        return [$application->fresh(), $offer, $offer->hiringConversion];
    }

    /** The new-employee form, filled in. */
    private function person(array $overrides = []): array
    {
        return array_merge([
            'firstNameEn' => 'Candidate', 'lastNameEn' => 'One',
            'firstNameAr' => 'مرشح', 'lastNameAr' => 'واحد',
            'fatherNameEn' => 'Father', 'fatherNameAr' => 'الأب',
            'motherNameEn' => 'Mother', 'motherNameAr' => 'الأم',
            'nationality' => (string) Nationality::Syrian,
            'nationalNumber' => '01234567890',
            'gender' => '1',
            'maritalStatus' => '1',
            'dob' => '1995-05-01',
            'personalPhone' => '0944000000',
            'personalEmail' => '',
            'username' => 'candidate.one',
        ], $overrides);
    }

    private function input(array $overrides = []): array
    {
        return array_merge(['mode' => Conversions::MODE_CREATE, 'employee' => $this->person()], $overrides);
    }

    private function hire(Conversion $conversion, array $overrides = []): Conversion
    {
        return $this->conversions()->convert($this->hr, $conversion->fresh(), $this->input($overrides));
    }

    /** Refused for $reason, recorded as a failed attempt, nothing created. */
    private function assertBlocked(Conversion $conversion, string $reason): void
    {
        $before = [Employee::query()->count(), Contract::query()->count(), (int) $conversion->fresh()->attempt_count];

        $this->assertValidationError(fn () => $this->hire($conversion), 'conversion', $reason);

        $conversion->refresh();
        $this->assertSame([Conversion::STATUS_FAILED, $reason, $before[2] + 1], [$conversion->status, $conversion->failure_reason, $conversion->attempt_count]);
        $this->assertSame([$before[0], $before[1]], [Employee::query()->count(), Contract::query()->count()]);
        $this->assertNull($conversion->contract_id);
    }

    /** @return array<string, array<int, string>> */
    private function assertValidationError(callable $action, string $field, ?string $errorKey = null, string $section = 'conversions'): array
    {
        try {
            $action();
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($field, $exception->errors(), 'Errors: '.json_encode($exception->errors()));
            if ($errorKey !== null) {
                $this->assertSame(__("recruitment-applicant.{$section}.errors.{$errorKey}"), $exception->errors()[$field][0]);
            }

            return $exception->errors();
        }

        $this->fail("Expected a validation error on '{$field}'.");
    }
}
