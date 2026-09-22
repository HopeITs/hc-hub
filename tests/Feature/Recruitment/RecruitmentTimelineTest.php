<?php

namespace Tests\Feature\Recruitment;

use App\Enums\RecruitmentEventType;
use App\Enums\RecruitmentRequestStatus as Status;
use App\Enums\RecruitmentRequestType as Type;
use App\Livewire\Recruitment\Requests\Details;
use App\Services\RecruitmentRequestChangeClassifier as Classifier;
use App\Services\RecruitmentRequestDraftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsRecruitmentRequests;
use Tests\Concerns\BuildsWorkflowFixtures;
use Tests\Concerns\CreatesHrFixtures;
use Tests\TestCase;

/**
 * What the record says happened, and what the screen shows of it.
 *
 * An audit trail that says "edited" and nothing more cannot answer the only
 * question an approver has when a request changes under them: does my approval
 * still stand? These tests hold the trail to naming the fields.
 */
class RecruitmentTimelineTest extends TestCase
{
    use BuildsRecruitmentRequests, BuildsWorkflowFixtures, CreatesHrFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('en');
    }

    /** RV-1b: the edit event names every field that moved, and to what. */
    public function test_an_edit_records_which_fields_changed(): void
    {
        [$requester, $payload] = $this->editableDraft();
        $request = app(RecruitmentRequestDraftService::class)->create($requester, $payload);

        app(RecruitmentRequestDraftService::class)->update(
            $requester,
            $request->fresh(),
            ['priority' => 'urgent'] + $payload,
        );

        $changes = $request->events()
            ->where('event_type', RecruitmentEventType::RequestEdited)
            ->latest('id')->first()->meta['changes'] ?? [];

        $this->assertArrayHasKey('priority', $changes);
        $this->assertSame($payload['priority'], $changes['priority']['from']);
        $this->assertSame('urgent', $changes['priority']['to']);
        $this->assertSame(Classifier::DESCRIPTIVE, $changes['priority']['category']);

        // A field that did not move is not reported as if it had.
        $this->assertArrayNotHasKey('position_id', $changes);
        $this->assertArrayNotHasKey('salary_amount', $changes);
    }

    /** An edit that moved nothing says nothing rather than listing everything. */
    public function test_an_edit_that_changes_nothing_records_no_change_list(): void
    {
        [$requester, $payload] = $this->editableDraft();
        $request = app(RecruitmentRequestDraftService::class)->create($requester, $payload);

        app(RecruitmentRequestDraftService::class)->update($requester, $request->fresh(), $payload);

        $event = $request->events()
            ->where('event_type', RecruitmentEventType::RequestEdited)
            ->latest('id')->first();

        $this->assertNull($event->meta['changes'] ?? null);
    }

    /** The severest category wins: money moved as well as wording. */
    public function test_the_classifier_reports_the_severest_category_of_a_payload_edit(): void
    {
        $classifier = app(Classifier::class);

        $before = ['priority' => 'normal', 'salary_amount' => 100, 'city_id' => 1];
        $after = ['priority' => 'urgent', 'salary_amount' => 200, 'city_id' => 1];

        $this->assertSame(Classifier::FINANCIAL, $classifier->classifyPayload($before, $after));
        $this->assertSame(Classifier::NOTHING, $classifier->classifyPayload($before, $before));

        // Structure outranks money.
        $this->assertSame(
            Classifier::SUBSTANTIVE,
            $classifier->classifyPayload($before, ['city_id' => 2] + $after),
        );
    }

    /**
     * The role is stored on the row, not derived when the row is read.
     *
     * Deriving it would let a change of role today rewrite who decided what
     * last month, which is the whole reason the column exists.
     */
    public function test_the_timeline_shows_the_role_recorded_on_the_event(): void
    {
        ['request' => $request, 'requester' => $requester] = $this->workflow();

        $request->events()->create([
            'event_type' => RecruitmentEventType::StepApproved,
            'from_status' => Status::UnderReview,
            'to_status' => Status::UnderReview,
            'actor_user_id' => $requester->getKey(),
            'actor_role' => 'program-manager',
            'comment' => 'Agreed.',
        ]);

        // The role on the user is something else entirely.
        $this->assertNotSame('program-manager', $requester->myRole());

        Livewire::actingAs($requester)
            ->test(Details::class, ['requestId' => $request->getKey()])
            ->assertSee('program-manager')
            ->assertSee('Agreed.');
    }

    /** The status move is shown, so a reader sees what the event did. */
    public function test_the_timeline_shows_the_status_the_event_moved_the_request_to(): void
    {
        ['request' => $request, 'requester' => $requester] = $this->workflow();

        $request->events()->create([
            'event_type' => RecruitmentEventType::ChangesRequested,
            'from_status' => Status::UnderReview,
            'to_status' => Status::ChangesRequested,
            'actor_user_id' => $requester->getKey(),
            'actor_role' => 'manager',
        ]);

        $changesRequested = Status::getLocalizedName(Status::ChangesRequested)['value'];

        Livewire::actingAs($requester)
            ->test(Details::class, ['requestId' => $request->getKey()])
            ->assertSee($changesRequested);
    }

    /** The change list reaches the screen with the field named, not its key. */
    public function test_the_timeline_shows_the_edited_fields_by_name(): void
    {
        ['request' => $request, 'requester' => $requester] = $this->workflow();

        $request->events()->create([
            'event_type' => RecruitmentEventType::RequestEdited,
            'to_status' => Status::Draft,
            'actor_user_id' => $requester->getKey(),
            'actor_role' => 'manager',
            'meta' => ['changes' => [
                'salary_amount' => ['category' => Classifier::FINANCIAL, 'from' => 100, 'to' => 250],
            ]],
        ]);

        Livewire::actingAs($requester)
            ->test(Details::class, ['requestId' => $request->getKey()])
            ->assertSee(__('recruitment.fields.salary_amount'))
            ->assertSee('250');
    }

    /**
     * A requester with a post beneath them, and a full payload aimed at it.
     *
     * The workflow fixture seats its requester *on* the position under review,
     * and a request is made for a post in one's tree rather than for one's own
     * chair — so an edit needs its own small chart.
     *
     * @return array{0: \App\Models\User, 1: array<string, mixed>}
     */
    private function editableDraft(): array
    {
        $city = $this->makeCity('SYR', '000');
        $requester = $this->makeUser($city, 'manager');
        $requester->givePermissionTo(\Spatie\Permission\Models\Permission::findOrCreate(
            'recruitment-requests.submit-without-manager', 'web',
        ));

        $root = \App\Models\Position::query()->create([
            'city_id' => $city->id,
            'program_id' => \App\Models\Program::query()->create([
                'name' => 'Finance', 'code' => 'FIN', 'slug' => 'finance',
                'start_date' => now(), 'is_active' => true,
            ])->id,
            'department_id' => \App\Models\Department::query()->create(['name' => 'Finance', 'code' => 'FIN'])->id,
            'job_level_id' => \App\Models\JobLevel::query()->create(['name_en' => 'Manager', 'name_ar' => 'مدير', 'grade' => 5])->id,
            'title_en' => 'Manager', 'title_ar' => 'مدير',
            'is_active' => true, 'max_emp' => 5, 'exist_emp' => 0,
        ]);

        \App\Models\Contract::query()->create([
            'employee_id' => $requester->employee_id, 'position_id' => $root->id,
            'contract_type_id' => $this->recruitmentContractTypeId(),
            'start_date' => now()->subMonth(), 'end_date' => now()->addYear(),
            'currency' => 2, 'salary_per' => 1, 'weekly_hours' => 40, 'is_active' => true,
        ]);

        $target = $root->replicate();
        $target->fill(['direct_manager_id' => $root->id, 'title_en' => 'Analyst', 'exist_emp' => 0])->save();

        $payload = ['request_type' => Type::FillVacancy, 'position_id' => $target->id]
            + $this->recruitmentFormDefaults();

        return [$requester, $payload];
    }
}
