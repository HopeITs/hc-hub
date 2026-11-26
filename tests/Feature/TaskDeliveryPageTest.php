<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Core\Models\User;
use Modules\Tracking\Enums\Currency;
use Modules\Tracking\Enums\DeliverableKind;
use Modules\Tracking\Enums\DeliveryState;
use Modules\Tracking\Enums\TaskStatus;
use Modules\Tracking\Models\Donor;
use Modules\Tracking\Models\DonorProposal;
use Modules\Tracking\Models\Proposal;
use Modules\Tracking\Models\Task;

/*
| The media delivery moves on the task page (tasks.show): deliver, accept and
| return, moved here from the retired Department Tasks page. The three
| actions' own rules are covered by DepartmentTaskDeliveryTest.
*/

beforeEach(function () {
    Storage::fake('local');
    $this->travelTo('2026-10-15 10:30:00');

    $this->media = insertHrDepartment('Media');
    $this->mediaEmployee = insertHrDepartmentManager($this->media);
    $this->mediaUser = userWithRole('Media Team', $this->mediaEmployee);

    $this->requester = User::factory()->role('Public Relations')->create();
    $this->proposal = Proposal::factory()->create(['name' => 'Winter Response', 'requested_budget' => 987654.32, 'requested_currency' => Currency::Usd]);
});

/**
 * A media deliverable of the Media department on the shared proposal.
 *
 * @param  list<DeliverableKind>  $kinds
 * @param  array<string, mixed>  $attributes
 */
function taskPageDeliverable(array $kinds = [DeliverableKind::Video], array $attributes = []): Task
{
    return Task::factory()
        ->forProposal(test()->proposal, test()->media)
        ->mediaDeliverable($kinds)
        ->create(['title' => 'Film the distribution', 'created_by' => test()->requester->id, ...$attributes]);
}

/**
 * The attributes of a deliverable already handed over and waiting for review.
 *
 * @return array<string, mixed>
 */
function taskPageDelivered(): array
{
    return ['delivery_state' => DeliveryState::Delivered, 'status' => TaskStatus::UnderReview, 'delivered_on' => '2026-10-14', 'delivered_link' => 'https://video.example/a'];
}

/**
 * The task page as a viewer.
 */
function taskPageAs(User $user, Task $task)
{
    return Livewire::actingAs($user)->test('tracking::tasks.show', ['task' => $task]);
}

test('the task page shows the delivery state and the move each person may make', function () {
    $task = taskPageDeliverable();

    taskPageAs($this->mediaUser, $task)
        ->assertSeeHtml('data-test="task-delivery"')
        ->assertSeeText(DeliveryState::Requested->label())
        ->assertSeeHtml('data-test="deliver-'.$task->id.'"')
        ->assertDontSeeHtml('data-test="accept-'.$task->id.'"');

    $delivered = taskPageDeliverable(attributes: taskPageDelivered());

    taskPageAs($this->requester, $delivered)
        ->assertSeeHtml('data-test="accept-'.$delivered->id.'"')
        ->assertSeeHtml('data-test="return-'.$delivered->id.'"')
        ->assertDontSeeHtml('data-test="deliver-'.$delivered->id.'"');

    taskPageAs($this->mediaUser, $delivered)
        ->assertDontSeeHtml('data-test="accept-'.$delivered->id.'"')
        ->assertDontSeeHtml('data-test="deliver-'.$delivered->id.'"');
});

test('a returned deliverable shows why and can be delivered again', function () {
    $task = taskPageDeliverable(attributes: [...taskPageDelivered(), 'delivery_state' => DeliveryState::Returned, 'status' => TaskStatus::InProgress, 'returned_reason' => 'The sound is missing.']);

    taskPageAs($this->mediaUser, $task)
        ->assertSeeHtml('data-returned-reason')
        ->assertSeeText('The sound is missing.')
        ->assertSeeHtml('data-test="deliver-'.$task->id.'"');
});

test('a task that is not a media deliverable has no delivery block', function () {
    $task = Task::factory()->forProposal($this->proposal, $this->media)->create(['created_by' => $this->requester->id]);

    taskPageAs($this->mediaUser, $task)
        ->assertDontSeeHtml('data-test="task-delivery"')
        ->assertDontSeeHtml('data-test="deliver-'.$task->id.'"');
});

test('the page shows no money and no proposal budget', function () {
    $donor = Donor::factory()->create();
    Proposal::query()->whereKey($this->proposal->id)->update(['donor_id' => $donor->id]);
    DonorProposal::factory()->create(['proposal_id' => $this->proposal->id, 'donor_id' => $donor->id, 'budget' => 555000, 'currency' => Currency::Eur]);
    $task = taskPageDeliverable();

    $html = taskPageAs($this->mediaUser, $task)->assertSeeText('Film the distribution')->html();

    expect($html)->not->toContain('987,654')
        ->not->toContain('987654')
        ->not->toContain('555,000')
        ->not->toContain('555000')
        ->not->toContain('USD')
        ->not->toContain('EUR');
});

test('another department\'s employee cannot open the deliverable', function () {
    $task = taskPageDeliverable();
    $financeEmployee = insertHrEmployee();
    insertHrContract($financeEmployee, insertHrPosition(insertHrDepartment('Finance')));

    $this->actingAs(userWithRole('Team Member', $financeEmployee))->get(route('tasks.show', $task))->assertNotFound();
});

test('a media employee delivers a video link from the task page and the requester accepts it', function () {
    $task = taskPageDeliverable();

    taskPageAs($this->mediaUser, $task)
        ->call('openDelivery')
        ->assertSet('showDeliveryModal', true)
        ->set('deliveredLink', 'https://video.example/distribution')
        ->call('deliver')
        ->assertHasNoErrors()
        ->assertSet('showDeliveryModal', false)
        ->assertSet('taskStatus', TaskStatus::UnderReview->value);

    expect($task->fresh()->delivery_state)->toBe(DeliveryState::Delivered);

    taskPageAs($this->requester, $task)
        ->call('confirmAccept')
        ->assertSet('showAcceptModal', true)
        ->call('accept')
        ->assertSet('showAcceptModal', false)
        ->assertSet('taskStatus', TaskStatus::Done->value)
        ->assertSet('progressPercentage', '100');

    expect($task->fresh())
        ->delivery_state->toBe(DeliveryState::Accepted)
        ->status->toBe(TaskStatus::Done);
});

test('a delivery without the required evidence or in the future is refused on its fields', function () {
    $task = taskPageDeliverable([DeliverableKind::Video]);

    taskPageAs($this->mediaUser, $task)
        ->call('openDelivery')
        ->call('deliver')
        ->assertHasErrors('deliveredLink')
        ->set('deliveredLink', 'https://video.example/a')
        ->set('deliveredOn', '2026-10-20')
        ->call('deliver')
        ->assertHasErrors('deliveredOn')
        ->assertSet('showDeliveryModal', true);

    expect($task->fresh()->delivery_state)->toBe(DeliveryState::Requested);
});

test('photos are delivered as files from the task page', function () {
    $task = taskPageDeliverable([DeliverableKind::Photos]);

    $this->travelBack();

    taskPageAs($this->mediaUser, $task)
        ->call('openDelivery')
        ->call('addDeliveryFileSlot')
        ->assertSet('deliveryFileSlots', 2)
        ->set('deliveryFiles.0', UploadedFile::fake()->image('crowd.jpg'))
        ->set('deliveryFiles.1', UploadedFile::fake()->image('stage.png'))
        ->call('deliver')
        ->assertHasNoErrors();

    expect(DB::table('task_attachments')->where('task_id', $task->id)->count())->toBe(2);
});

test('a return needs a reason and a stale view writes nothing', function () {
    $task = taskPageDeliverable(attributes: taskPageDelivered());

    $page = taskPageAs($this->requester, $task)
        ->call('confirmReturn')
        ->assertSet('showReturnModal', true)
        ->call('returnDelivery')
        ->assertHasErrors('returnReason');

    Task::query()->withoutGlobalScopes()->whereKey($task->id)->update(['delivery_state' => DeliveryState::Accepted->value]);

    $page->set('returnReason', 'The sound is missing.')
        ->call('returnDelivery')
        ->assertHasErrors('returnReason')
        ->assertSee(__('department_tasks.stale_delivery_state'));

    expect($task->fresh()->delivery_state)->toBe(DeliveryState::Accepted)
        ->and($task->fresh()->returned_reason)->toBeNull();
});

test('a valid return sends the deliverable back with the reason', function () {
    $task = taskPageDeliverable(attributes: taskPageDelivered());

    taskPageAs($this->requester, $task)
        ->call('confirmReturn')
        ->set('returnReason', 'The sound is missing.')
        ->call('returnDelivery')
        ->assertHasNoErrors()
        ->assertSet('showReturnModal', false);

    expect($task->fresh())
        ->delivery_state->toBe(DeliveryState::Returned)
        ->returned_reason->toBe('The sound is missing.');
});

test('accepting from a state someone else already changed is refused with a toast and writes nothing', function () {
    $task = taskPageDeliverable(attributes: taskPageDelivered());

    $page = taskPageAs($this->requester, $task)->call('confirmAccept');

    Task::query()->withoutGlobalScopes()->whereKey($task->id)->update(['delivery_state' => DeliveryState::Returned->value]);

    $page->call('accept')->assertSet('showAcceptModal', false);

    expect($task->fresh()->delivery_state)->toBe(DeliveryState::Returned);
});

test('someone who may not review is refused and nothing is written', function () {
    $task = taskPageDeliverable(attributes: taskPageDelivered());

    taskPageAs($this->mediaUser, $task)
        ->call('confirmAccept')
        ->call('accept')
        ->assertForbidden();

    expect($task->fresh()->delivery_state)->toBe(DeliveryState::Delivered);
});

test('the retired Department Tasks page is gone', function () {
    $this->actingAs($this->mediaUser)->get('/department-tasks')->assertNotFound();
});
