<?php

use App\Models\Activity;
use App\Models\Role;
use App\Support\Modules\Contracts\WorkItemSource;
use App\Support\Modules\ModuleRegistry;
use Livewire\Livewire;
use Modules\Core\Models\User;
use Modules\Hr\Enums\LeaveRequestStatus;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\LeaveBalance;
use Modules\Hr\Models\LeaveRequest;
use Modules\Hr\Models\OnboardingJourney;
use Modules\Hr\Support\Inbox\WorkInbox;
use Modules\Tracking\Enums\ProposalStatus;
use Modules\Tracking\Enums\ReviewStage;
use Modules\Tracking\Enums\TaskStatus;
use Modules\Tracking\Models\Proposal;
use Modules\Tracking\Models\ProposalReviewer;
use Modules\Tracking\Models\Task;
use Spatie\Permission\Models\Permission;

/*
| Waiting on me (roadmap 4.9, D7): the items of the HR inbox, of the Desk's
| Needs you and of My reviews on one page, each place with the count its own
| page or card reports. The old places keep working (the rollback rule).
*/

/**
 * An HR login that also takes part in Tracking: they confirm leave and close
 * journeys (HR inbox), approve tasks they asked for (Needs you) and review
 * proposals (My reviews).
 */
function waitingViewer(): User
{
    $user = User::factory()->create(['employee_id' => Employee::factory()->create()->id]);
    $user->assignRole(Role::findOrCreate('HR-SYR', 'web'));

    foreach (['leaves.review', 'employees.update', 'tasks.view'] as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }

    return $user->fresh();
}

/**
 * One item in each place, two of them listed by two places:
 *  - a decided leave to confirm: HR inbox and Needs you;
 *  - an open onboarding journey: HR inbox only;
 *  - a task sent back for the viewer's approval: Needs you only;
 *  - a concept note to review: My reviews and Needs you.
 */
function waitingWork(User $viewer): void
{
    $onLeave = Employee::factory()->create(['first_name_en' => 'Layla', 'last_name_en' => 'Nassar']);
    LeaveRequest::factory()->for(LeaveBalance::factory()->for(Contract::factory()->for($onLeave)->create(['is_active' => true]))->create())->create([
        'status' => LeaveRequestStatus::Accepted->value,
        'approved_by' => Employee::factory()->create()->id,
        'approved_at' => now(),
    ]);

    $newcomer = Employee::factory()->create(['first_name_en' => 'Rami', 'last_name_en' => 'Saad']);
    (new OnboardingJourney)->forceFill(['employee_id' => $newcomer->id, 'started_at' => now()->subDays(3), 'orientation_due_on' => now()->addDays(4)->toDateString()])->save();

    Activity::withoutRecording(function () use ($viewer): void {
        $task = Task::factory()->create(['created_by' => $viewer->id, 'title' => 'Survey the wells', 'status' => TaskStatus::UnderReview]);
        $task->taskAssignees()->delete();
        $task->taskAssignees()->create(['employee_id' => Employee::factory()->create()->id]);
    });

    ProposalReviewer::factory()->create([
        'proposal_id' => Proposal::factory()->create(['name' => 'Water Access', 'status' => ProposalStatus::ConceptNoteReview])->id,
        'stage' => ReviewStage::ConceptNote,
        'employee_id' => $viewer->employee_id,
    ]);
}

/**
 * The number a Desk card or a register prints beside its title.
 */
function printedCount(string $html, string $marker): int
{
    expect(preg_match('/'.preg_quote($marker, '/').'>(\d+)</', $html, $found))->toBe(1);

    return (int) $found[1];
}

test('HR, Tracking and the platform each register one place where work waits', function () {
    $keys = array_map(fn (WorkItemSource $source): string => $source->key(), app(ModuleRegistry::class)->workItemSources());

    expect($keys)->toContain('hr-inbox', 'needs', 'reviews')->toHaveCount(3);
});

test('the page lists the items of the HR inbox, Needs you and My reviews with the count each of them reports', function () {
    $viewer = waitingViewer();
    waitingWork($viewer);

    $inbox = printedCount(Livewire::actingAs($viewer)->test('hr::inbox.index')->assertOk()->html(), 'data-count-badge');
    $needs = printedCount(Livewire::actingAs($viewer)->test('desk.panels.needs', ['size' => 'L'])->html(), 'data-panel-count');
    $reviews = printedCount(Livewire::actingAs($viewer)->test('desk.panels.reviews', ['size' => 'M'])->html(), 'data-panel-count');

    expect([$inbox, $needs, $reviews])->toBe([2, 3, 1])
        ->and(app(WorkInbox::class)->count($viewer))->toBe($inbox);

    $page = Livewire::actingAs($viewer)->test('pages::waiting.index')
        ->assertOk()
        ->assertSee(['Layla Nassar', 'Rami Saad', 'Survey the wells', 'Water Access'])
        ->assertSeeHtml('data-waiting-count="hr-inbox">'.$inbox.'<')
        ->assertSeeHtml('data-waiting-count="needs">'.$needs.'<')
        ->assertSeeHtml('data-waiting-count="reviews">'.$reviews.'<');

    expect(printedCount($page->html(), 'data-count-badge'))->toBe(4)
        ->and(substr_count($page->html(), 'data-waiting-row='))->toBe(4);
});

test('picking a place shows the rows of that place, as many as its count', function (string $source, int $rows, array $seen, array $hidden) {
    $viewer = waitingViewer();
    waitingWork($viewer);

    $page = Livewire::actingAs($viewer)->test('pages::waiting.index')
        ->set('source', $source)
        ->assertSee($seen)
        ->assertDontSee($hidden);

    expect(substr_count($page->html(), 'data-waiting-row="'.$source.'"'))->toBe($rows)
        ->and(substr_count($page->html(), 'data-waiting-row='))->toBe($rows);
})->with([
    'the HR inbox' => ['hr-inbox', 2, ['Layla Nassar', 'Rami Saad'], ['Survey the wells', 'Water Access']],
    'Needs you' => ['needs', 3, ['Layla Nassar', 'Survey the wells', 'Water Access'], ['Rami Saad']],
    'My reviews' => ['reviews', 1, ['Water Access'], ['Layla Nassar', 'Rami Saad', 'Survey the wells']],
]);

test('a place that does not exist reads as everything, and the search narrows the rows', function () {
    $viewer = waitingViewer();
    waitingWork($viewer);

    Livewire::actingAs($viewer)->test('pages::waiting.index')
        ->set('source', 'no-such-place')
        ->assertSee(['Layla Nassar', 'Rami Saad', 'Survey the wells', 'Water Access'])
        ->set('search', 'wells')
        ->assertSee('Survey the wells')
        ->assertDontSee('Layla Nassar')
        ->set('search', 'nothing like this')
        ->assertSee(__('waiting.empty_filtered'));
});

test('someone with nothing waiting sees an empty page and no HR place, never the work of another person', function () {
    waitingWork(waitingViewer());
    $other = User::factory()->create(['employee_id' => Employee::factory()->create()->id]);

    Livewire::actingAs($other)->test('pages::waiting.index')
        ->assertOk()
        ->assertSee(__('waiting.empty'))
        ->assertDontSeeHtml('data-waiting-count="hr-inbox"')
        ->assertSeeHtml('data-waiting-count="needs">0<')
        ->assertDontSee(['Layla Nassar', 'Rami Saad', 'Survey the wells', 'Water Access']);
});

test('the page is for signed-in people only', function () {
    $this->get(route('waiting.index'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create())->get(route('waiting.index'))->assertOk()->assertSee(__('titles.waiting'));
});
