<?php

namespace Tests\Feature\Recruitment;

use App\Enums\RecruitmentRequestStatus as Status;
use App\Enums\RecruitmentRequestType as Type;
use App\Models\Contract;
use App\Models\ContractType;
use App\Models\Department;
use App\Models\JobLevel;
use App\Models\Position;
use App\Models\Program;
use App\Models\RecruitmentRequest;
use App\Models\User;
use App\Models\Vacancy;
use App\Services\RecruitmentRequestDraftService;
use App\Services\RecruitmentRequestSubmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\CreatesHrFixtures;
use Tests\TestCase;

/**
 * Draft → submitted: completeness, versioning and the duplicate rule.
 */
class RecruitmentSubmissionTest extends TestCase
{
    use CreatesHrFixtures, RefreshDatabase;

    private User $manager;

    private Position $root;

    private Position $target;

    private ContractType $contractType;

    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('en');

        $city = $this->makeCity('SYR', '000');
        $this->manager = $this->makeUser($city, 'manager');
        $this->contractType = ContractType::query()->create(['name_en' => 'Annual', 'name_ar' => 'سنوي']);

        $this->root = Position::query()->create([
            'city_id' => $city->id,
            'program_id' => Program::query()->create(['name' => 'Finance', 'code' => 'FIN', 'slug' => 'finance', 'start_date' => now(), 'is_active' => true])->id,
            'department_id' => Department::query()->create(['name' => 'Finance', 'code' => 'FIN'])->id,
            'job_level_id' => JobLevel::query()->create(['name_en' => 'Manager', 'name_ar' => 'مدير', 'grade' => 5])->id,
            'title_en' => 'Manager', 'title_ar' => 'مدير',
            'is_active' => true, 'max_emp' => 5, 'exist_emp' => 0,
        ]);

        Contract::query()->create([
            'employee_id' => $this->manager->employee_id, 'position_id' => $this->root->id,
            'contract_type_id' => $this->contractType->id,
            'start_date' => now()->subMonth(), 'end_date' => now()->addYear(),
            'currency' => 2, 'salary_per' => 1, 'weekly_hours' => 40, 'is_active' => true,
        ]);

        $this->target = $this->root->replicate();
        $this->target->fill(['direct_manager_id' => $this->root->id, 'title_en' => 'Analyst', 'exist_emp' => 0])->save();
    }

    /** A draft may be half-finished; a submission may not. */
    public function test_submitting_an_incomplete_draft_names_every_missing_field(): void
    {
        $request = $this->draft([]);

        try {
            $this->submit($request);
            $this->fail('An incomplete draft should not submit.');
        } catch (ValidationException $exception) {
            foreach (['justification', 'needed_by_date', 'salary_amount', 'currency', 'salary_per', 'contract_type_id', 'finance_consulted'] as $field) {
                $this->assertArrayHasKey('data.'.$field, $exception->errors(), "{$field} should be reported as missing.");
            }
        }

        $this->assertSame(Status::Draft, (int) $request->fresh()->status);
        $this->assertSame(0, $request->versions()->count());
    }

    /** finance_consulted is an attestation: leaving it unticked is not an answer. */
    public function test_an_unticked_finance_attestation_is_missing_not_false(): void
    {
        $request = $this->draft(['finance_consulted' => false]);

        try {
            $this->submit($request);
            $this->fail('An unticked attestation should not submit.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('data.finance_consulted', $exception->errors());
        }
    }

    public function test_a_complete_draft_submits_and_freezes_version_one(): void
    {
        $request = $this->submit($this->draft($this->complete()));

        $this->assertSame(Status::Submitted, (int) $request->status);
        $this->assertNotNull($request->submitted_at);

        $version = $request->versions()->sole();
        $this->assertSame(1, (int) $version->version_no);
        $this->assertSame($this->manager->id, (int) $version->created_by_user_id);

        // The details row carries the column names RV-1 compares, not the
        // draft's own field names.
        $detail = $version->positionDetail;
        $this->assertSame('Because we need one', $detail->recruitment_reason);
        $this->assertSame('2026-12-01', $detail->expected_start_date->format('Y-m-d'));
        $this->assertSame($this->target->id, (int) $detail->position_id);
        $this->assertSame(5, (int) $detail->current_headcount);
        $this->assertTrue((bool) $detail->finance_consulted);
    }

    /**
     * Idempotent without a stored key: the row is locked and the status read
     * again, so a second click returns the same request rather than a version 2.
     */
    public function test_submitting_twice_produces_one_version(): void
    {
        $request = $this->draft($this->complete());

        $first = $this->submit($request);
        $second = $this->submit($request);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, $request->versions()->count());
        $this->assertSame(Status::Submitted, (int) $second->status);
    }

    /** Same position, same type, while the first is still being processed. */
    public function test_a_second_active_request_of_the_same_type_on_one_position_is_refused(): void
    {
        $this->submit($this->draft($this->complete()));
        $second = $this->draft($this->complete());

        try {
            $this->submit($second);
            $this->fail('A duplicate active request should be refused.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('data.position_id', $exception->errors());
        }
    }

    /** A different type on the same position is a different, legitimate ask. */
    public function test_a_different_type_on_the_same_position_is_allowed(): void
    {
        $this->submit($this->draft($this->complete()));

        $increase = $this->draft(array_merge($this->complete(), ['additional_count' => 2]), Type::IncreaseHeadcount);

        $this->assertSame(Status::Submitted, (int) $this->submit($increase)->status);
    }

    /** Rejected and cancelled are final, so they release the position at once. */
    public function test_a_rejected_request_frees_the_position_immediately(): void
    {
        $first = $this->submit($this->draft($this->complete()));
        $first->forceFill(['status' => Status::Rejected])->save();

        $second = $this->submit($this->draft($this->complete()));

        $this->assertSame(Status::Submitted, (int) $second->status);
    }

    /** Filling a vacancy and closing it are contradictory intentions. */
    public function test_fill_and_close_cannot_both_be_active_on_one_vacancy(): void
    {
        $vacancy = Vacancy::query()->create([
            'position_id' => $this->target->id,
            'created_from_request_id' => $this->draft($this->complete())->id,
            'status' => 'open', 'needed_count' => 1, 'filled_count' => 0, 'open_at' => now(),
        ]);

        $fill = $this->draft(array_merge($this->complete(), ['vacancy_id' => $vacancy->id]));
        $this->submit($fill);

        $close = $this->draft(['vacancy_id' => $vacancy->id, 'reason' => 'No longer needed'], Type::CloseVacancy);

        try {
            $this->submit($close);
            $this->fail('Closing a vacancy that is being filled should be refused.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('data.vacancy_id', $exception->errors());
        }
    }

    /** The establishment may not fall below the people already in it. */
    public function test_a_reduction_below_the_existing_employees_is_refused(): void
    {
        $this->target->forceFill(['exist_emp' => 4, 'max_emp' => 5])->save();

        $request = $this->draft(['reduce_by' => 3, 'reason' => 'Restructure', 'effective_date' => '2026-12-01'], Type::DecreaseHeadcount);

        try {
            $this->submit($request);
            $this->fail('Reducing below the existing employees should be refused.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('data.reduce_by', $exception->errors());
        }
    }

    /** Editing a draft rewrites it in place; no version exists until submit. */
    public function test_a_draft_can_be_edited_before_submitting(): void
    {
        $request = $this->draft($this->complete());

        app(RecruitmentRequestDraftService::class)->update(
            $this->manager,
            $request,
            $this->payload(['justification' => 'A better reason']),
        );

        $this->assertSame('A better reason', $request->fresh()->draft_payload_json['justification']);
        $this->assertSame(0, $request->versions()->count());
    }

    /** A submitted request is no longer the requester's private working copy. */
    public function test_a_submitted_request_can_no_longer_be_edited(): void
    {
        $request = $this->submit($this->draft($this->complete()));

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

        app(RecruitmentRequestDraftService::class)->update($this->manager, $request, $this->payload());
    }

    /** Resubmitting after changes were requested freezes version 2 (T11). */
    public function test_resubmitting_after_changes_creates_the_next_version(): void
    {
        $request = $this->submit($this->draft($this->complete()));
        $request->forceFill(['status' => Status::ChangesRequested])->save();

        app(RecruitmentRequestDraftService::class)->update(
            $this->manager,
            $request,
            $this->payload(['justification' => 'Revised']),
        );

        $resubmitted = $this->submit($request);

        $this->assertSame(Status::Resubmitted, (int) $resubmitted->status);
        $this->assertSame([1, 2], $request->versions()->orderBy('version_no')->pluck('version_no')->map(fn ($n) => (int) $n)->all());
        $this->assertSame('Revised', $request->versions()->where('version_no', 2)->sole()->positionDetail->recruitment_reason);
    }

    public function test_a_draft_can_be_cancelled_and_cancelling_twice_is_harmless(): void
    {
        $request = $this->draft($this->complete());
        $submissions = app(RecruitmentRequestSubmissionService::class);

        $submissions->cancel($this->manager, $request);
        $this->assertSame(Status::Cancelled, (int) $request->fresh()->status);

        $submissions->cancel($this->manager, $request->fresh());
        $this->assertSame(Status::Cancelled, (int) $request->fresh()->status);
    }

    /** @return array<string, mixed> */
    private function complete(): array
    {
        return [
            'justification' => 'Because we need one',
            'needed_by_date' => '2026-12-01',
            'salary_amount' => 500,
            'currency' => 2,
            'salary_per' => 1,
            'contract_type_id' => $this->contractType->id,
            'finance_consulted' => true,
        ];
    }

    /** Exactly what is passed, so a test can deliberately leave fields out. */
    private function draft(array $payload, int $type = Type::FillVacancy): RecruitmentRequest
    {
        return app(RecruitmentRequestDraftService::class)->create($this->manager, array_merge([
            'request_type' => $type,
            'position_id' => $this->target->id,
        ], $payload));
    }

    /** A full, valid payload — update() re-validates everything, not a diff. */
    private function payload(array $overrides = [], int $type = Type::FillVacancy): array
    {
        return array_merge([
            'request_type' => $type,
            'position_id' => $this->target->id,
        ], $this->complete(), $overrides);
    }

    private function submit(RecruitmentRequest $request): RecruitmentRequest
    {
        return app(RecruitmentRequestSubmissionService::class)->submit($this->manager, $request->fresh());
    }
}
