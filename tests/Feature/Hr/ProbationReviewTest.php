<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Modules\Core\Models\City;
use Modules\Core\Models\User;
use Modules\Hr\Console\StartProbationReviewsCommand;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\ContractType;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\Position;
use Modules\Hr\Models\ProbationReview;
use Modules\Hr\Support\Contracts\ProbationReviews;
use Modules\Hr\Support\Inbox\WorkInbox;
use Modules\Hr\Support\Journeys\Notifications\JourneyNotice;
use Modules\Hr\Support\Settings\HrAccess;
use Spatie\Permission\Models\Permission;

/*
| The last-third review of a trial: a running trial that has used two thirds of
| its term gets one review, the direct manager answers with extend, confirm or
| cancel and a reason, and HR is told. Nothing is applied to the contract.
| "Today" is Wednesday 2026-10-21; the trial below runs 2026-08-01 to 2026-11-01
| (92 days), so its last third starts on 2026-10-01.
*/

beforeEach(function () {
    HrAccess::flush();
    $this->travelTo('2026-10-21 10:00:00');
    Notification::fake();

    foreach ([1, 3] as $id) {
        ContractType::query()->find($id) ?? ContractType::factory()->create(['id' => $id]);
    }

    $this->city = City::factory()->create();
    $this->managerPosition = Position::factory()->create(['city_id' => $this->city->id]);
    $this->manager = User::factory()->create();
    Contract::factory()->create(['employee_id' => $this->manager->employee_id, 'position_id' => $this->managerPosition->id, 'is_active' => true, 'contract_type_id' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
    $this->hr = User::factory()->role('HR-'.strtoupper($this->city->slug))->create();
    $this->hr->givePermissionTo(Permission::findOrCreate('employees.update', 'web'));
    $this->hr = $this->hr->fresh();

    $this->volunteer = User::factory()->create(['employee_id' => Employee::factory()->create(['city_id' => $this->city->id])->id]);
    $this->position = Position::factory()->create(['city_id' => $this->city->id, 'direct_manager_id' => $this->managerPosition->id]);

    $this->trial = fn (array $attributes = []): Contract => Contract::factory()->create($attributes + [
        'employee_id' => $this->volunteer->employee_id, 'position_id' => $this->position->id, 'is_active' => true,
        'contract_type_id' => 3, 'start_date' => '2026-08-01', 'end_date' => '2026-11-01',
    ]);

    $this->kinds = fn (User $user): array => Notification::sent($user, JourneyNotice::class)
        ->map(fn (JourneyNotice $notice): string => $notice->kind)
        ->filter(fn (string $kind): bool => str_starts_with($kind, 'probation-'))
        ->values()
        ->all();
});

test('a trial in its last third gets one review and each side is told, once', function () {
    $trial = ($this->trial)();

    $this->artisan(StartProbationReviewsCommand::class)->assertSuccessful();
    $this->artisan(StartProbationReviewsCommand::class)->assertSuccessful();

    expect(ProbationReview::query()->count())->toBe(1)
        ->and(ProbationReview::query()->sole()->contract_id)->toBe($trial->id)
        ->and(($this->kinds)($this->volunteer))->toBe(['probation-employee'])
        ->and(($this->kinds)($this->manager))->toBe(['probation-manager'])
        ->and(($this->kinds)($this->hr))->toBe(['probation-hr']);
});

test('only a running trial that reached its last third starts a review', function () {
    ($this->trial)(['start_date' => '2026-10-01', 'end_date' => '2027-01-01']);
    ($this->trial)(['contract_type_id' => 1]);
    ($this->trial)(['is_active' => false]);
    ($this->trial)(['terminated_at' => '2026-10-10']);
    ($this->trial)(['start_date' => '2026-06-01', 'end_date' => '2026-09-01']);

    expect(app(ProbationReviews::class)->startDue())->toBe(0)
        ->and(ProbationReview::query()->count())->toBe(0);
});

test('the manager is reminded every week until they answer', function () {
    ($this->trial)();
    $this->artisan(StartProbationReviewsCommand::class);

    $this->travelTo('2026-10-27 10:00:00');
    $this->artisan(StartProbationReviewsCommand::class);
    expect(($this->kinds)($this->manager))->toBe(['probation-manager']);

    $this->travelTo('2026-10-28 10:00:00');
    $this->artisan(StartProbationReviewsCommand::class);
    $this->artisan(StartProbationReviewsCommand::class);
    expect(($this->kinds)($this->manager))->toBe(['probation-manager', 'probation-manager-reminder']);

    $this->travelTo('2026-11-04 10:00:00');
    $this->artisan(StartProbationReviewsCommand::class);
    expect(($this->kinds)($this->manager))->toBe(['probation-manager', 'probation-manager-reminder']);
});

test('the direct manager asks HR with a reason, once, and the contract is not touched', function () {
    $trial = ($this->trial)();
    $this->artisan(StartProbationReviewsCommand::class);
    $review = ProbationReview::query()->sole();
    $before = Contract::query()->find($trial->id)->only(['end_date', 'is_active', 'terminated_at', 'contract_type_id']);

    Livewire::actingAs($this->manager)->test('hr::probation.review', ['review' => $review->id])
        ->assertSeeHtml('data-probation-form')
        ->call('send')
        ->assertHasErrors(['decision', 'reason'])
        ->set('decision', 'extend')
        ->set('reason', '   ')
        ->call('send')
        ->assertHasErrors(['reason'])
        ->set('reason', 'Needs two more months on the new system.')
        ->call('send')
        ->assertHasNoErrors()
        ->assertSeeHtml('data-probation-decided');

    expect($review->fresh())->decision->toBe('extend')->reason->toBe('Needs two more months on the new system.')->decided_by->toBe($this->manager->id)
        ->and(($this->kinds)($this->hr))->toBe(['probation-hr', 'probation-requested'])
        ->and(Contract::query()->find($trial->id)->only(['end_date', 'is_active', 'terminated_at', 'contract_type_id']))->toEqual($before);

    expect(app(ProbationReviews::class)->request($review->fresh(), 'cancel', 'Changed my mind.', $this->manager))->toBeFalse()
        ->and($review->fresh()->decision)->toBe('extend');
});

test('a stranger cannot open it, HR can read it but only answers when nobody runs the position', function () {
    ($this->trial)();
    $this->artisan(StartProbationReviewsCommand::class);
    $review = ProbationReview::query()->sole();
    $stranger = User::factory()->create();

    Livewire::actingAs($stranger)->test('hr::probation.review', ['review' => $review->id])->assertForbidden();

    Livewire::actingAs($this->hr)->test('hr::probation.review', ['review' => $review->id])
        ->assertSeeHtml('data-probation-waiting')
        ->assertDontSeeHtml('data-probation-form');

    expect(Gate::forUser($this->hr)->allows('decide', $review))->toBeFalse();

    Contract::query()->where('position_id', $this->managerPosition->id)->update(['is_active' => false, 'end_date' => '2026-09-01']);

    expect(Gate::forUser(User::query()->find($this->hr->id))->allows('decide', $review->fresh()))->toBeTrue();
});

test('the direct manager finds the unanswered review in the inbox until they answer', function () {
    ($this->trial)();
    $this->artisan(StartProbationReviewsCommand::class);
    $review = ProbationReview::query()->sole();

    $rows = app(WorkInbox::class)->for($this->manager)['rows']->where('key', "probation:{$review->id}:journey");

    expect($rows)->toHaveCount(1)
        ->and($rows->first()['href'])->toBe(route('hr.probation.show', $review));

    app(ProbationReviews::class)->request($review, 'confirm', 'Doing well.', User::query()->find($this->manager->id));

    expect(app(ProbationReviews::class)->pendingFor($this->manager)->count())->toBe(0);
});

test('the review runs every evening before the contract sweeps', function () {
    $events = collect(app(Schedule::class)->events())
        ->mapWithKeys(fn ($event): array => [trim((string) preg_replace('/^.*artisan"?\s+/', '', (string) $event->command)) => $event]);

    expect($events['probation:start-reviews']->expression)->toBe('20 21 * * *');
});
