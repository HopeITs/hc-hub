<?php

use App\Models\NotificationMute;
use App\Models\UserNotification;
use App\Notifications\AppNotification;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Support\Facades\Notification;
use Modules\Core\Models\City;
use Modules\Core\Models\User;
use Modules\Hr\Enums\LeaveRequestStatus;
use Modules\Hr\Models\Checklist;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\LeaveBalance;
use Modules\Hr\Models\LeaveRequest;
use Modules\Hr\Models\Position;
use Modules\Hr\Support\Checklists\ChecklistNotifier;
use Modules\Hr\Support\Leave\LeaveNotificationService;
use Modules\Hr\Support\Recruitment\Notifications\InboxChannel;
use Modules\Hr\Support\Recruitment\Notifications\NamesItsRecipient;

/*
| Hub roadmap 4.8 (D7): HR's inbox notifications go through RecipientFilter.
| The people told are the ones HR always told; the only difference is that a
| team-wide notice no longer reaches someone who muted its module. A notice
| addressed to the person themself (their own request, a decision that waits
| on them) still arrives, as the settings page promises.
*/

beforeEach(function () {
    Notification::fake();

    $city = City::factory()->create();
    $managerPosition = Position::factory()->create();
    $this->manager = User::factory()->create();
    Contract::factory()->create(['employee_id' => $this->manager->employee_id, 'position_id' => $managerPosition->id, 'is_active' => true]);
    $this->hr = User::factory()->role('HR-'.strtoupper($city->slug))->create();

    $this->requester = User::factory()->create(['employee_id' => Employee::factory()->create(['city_id' => $city->id])->id]);
    $contract = Contract::factory()->create([
        'employee_id' => $this->requester->employee_id,
        'position_id' => Position::factory()->create(['direct_manager_id' => $managerPosition->id])->id,
        'is_active' => true,
    ]);
    $this->balance = LeaveBalance::factory()->for($contract)->create();
    $this->leave = LeaveRequest::factory()->for($this->balance)->create(['status' => LeaveRequestStatus::Pending->value]);

    $this->keysFor = fn (User $user): array => Notification::sent($user, AppNotification::class)
        ->filter(fn (AppNotification $notification): bool => in_array($notification->payload->module, ['leaves', 'checklists', 'recruitment'], true))
        ->map(fn (AppNotification $notification): string => str_replace(['hr_leave.notifications.', 'hr_checklists.notifications.'], '', $notification->payload->key))
        ->values()
        ->all();
    $this->heard = fn (): array => [
        'requester' => ($this->keysFor)($this->requester),
        'manager' => ($this->keysFor)($this->manager),
        'hr' => ($this->keysFor)($this->hr),
    ];
    $this->mute = fn (User $user, string $module) => NotificationMute::query()->create(['user_id' => $user->id, 'module' => $module]);
    $this->service = app(LeaveNotificationService::class);
});

test('a new leave request tells the direct manager and city HR, not the requester', function () {
    $this->service->sendNewRequest($this->leave, $this->requester);

    expect(($this->heard)())->toBe(['requester' => [], 'manager' => ['new_request'], 'hr' => ['new_request']]);
});

test('a leave decision tells the requester and city HR, not the manager who decided', function () {
    $this->leave->update(['status' => LeaveRequestStatus::Accepted->value]);

    $this->service->sendDecision($this->leave, $this->manager);

    expect(($this->heard)())->toBe(['requester' => ['accepted_you'], 'manager' => [], 'hr' => ['accepted']]);
});

test('an HR review tells the requester and the manager, not the HR reviewer', function () {
    $this->service->sendHrReview($this->leave, $this->hr);

    expect(($this->heard)())->toBe(['requester' => ['seen_you'], 'manager' => ['seen'], 'hr' => []]);
});

test('an HR objection tells the manager and the requester', function () {
    $this->service->sendObjection($this->leave, $this->hr, 'Overlaps the audit week');

    expect(($this->heard)())->toBe(['requester' => ['objected_you'], 'manager' => ['objected'], 'hr' => []]);
});

test('a balance change tells the employee and their manager', function () {
    $this->service->sendBalanceChange($this->balance, $this->hr, 'sum', '2', 'Carried over');

    expect(($this->heard)())->toBe(['requester' => ['balance_added_you'], 'manager' => ['balance_added'], 'hr' => []]);
});

test('a checklist hand-over tells the person who must deliver it, even when they assigned it themself', function () {
    $checklist = (new Checklist)->forceFill([
        'employee_id' => $this->requester->employee_id,
        'delivered_id' => $this->manager->employee_id,
        'receiver_id' => $this->hr->employee_id,
    ]);

    $told = $this->actingAs($this->manager)->app->make(ChecklistNotifier::class)->assigned($checklist, ChecklistNotifier::DELIVER);

    expect($told)->toBeTrue()
        ->and(($this->heard)())->toBe(['requester' => [], 'manager' => ['deliver'], 'hr' => []]);
});

test('a team-wide leave notice skips whoever muted leave, and nobody else', function () {
    ($this->mute)($this->hr, 'leaves');

    $this->service->sendNewRequest($this->leave, $this->requester);
    $this->service->sendBalanceChange($this->balance, null, 'sum', '2', null);

    expect(($this->heard)())->toBe([
        'requester' => ['balance_added_you'],
        'manager' => ['new_request', 'balance_added'],
        'hr' => [],
    ]);
});

test('muting leave never hides what is addressed to me: my own request, or a decision waiting on me', function () {
    ($this->mute)($this->requester, 'leaves');
    ($this->mute)($this->manager, 'leaves');
    $this->leave->update(['status' => LeaveRequestStatus::Accepted->value]);

    $this->service->sendNewRequest($this->leave, $this->requester);
    $this->service->sendDecision($this->leave, null);
    $this->service->sendHrReview($this->leave, $this->hr);
    $this->service->sendObjection($this->leave, $this->hr, 'Overlaps the audit week');

    expect(($this->keysFor)($this->requester))->toBe(['accepted_you', 'seen_you', 'objected_you'])
        ->and(($this->keysFor)($this->manager))->toBe(['new_request', 'objected']);
});

test('a mute on another module changes nothing', function () {
    ($this->mute)($this->hr, 'tasks');
    ($this->mute)($this->manager, 'recruitment');

    $this->service->sendNewRequest($this->leave, $this->requester);

    expect(($this->heard)())->toBe(['requester' => [], 'manager' => ['new_request'], 'hr' => ['new_request']]);
});

test('a team-wide recruitment notice skips whoever muted recruitment; one that names me still arrives', function () {
    $teamWide = new class extends BaseNotification
    {
        /** @return array<string, mixed> */
        public function toArray(object $notifiable): array
        {
            return ['title' => ['en' => 'Campaign launched', 'ar' => 'انطلقت الحملة'], 'body' => 'R-1', 'url' => route('hr.recruitment.requests.index')];
        }
    };
    $named = new class extends BaseNotification implements NamesItsRecipient
    {
        /** @return array<string, mixed> */
        public function toArray(object $notifiable): array
        {
            return ['title' => 'Waiting on you', 'body' => 'R-1', 'url' => route('hr.recruitment.requests.index')];
        }
    };
    ($this->mute)($this->hr, 'recruitment');
    $channel = app(InboxChannel::class);

    $channel->send($this->manager, $teamWide);
    $channel->send($this->hr, $teamWide);
    $channel->send($this->hr, $named);

    $told = fn (User $user): array => Notification::sent($user, AppNotification::class)
        ->filter(fn (AppNotification $notification): bool => $notification->payload->module === 'recruitment')
        ->map(fn (AppNotification $notification): array => [$notification->payload->params['title'], $notification->payload->link])
        ->values()
        ->all();
    $link = ['route' => 'hr.recruitment.requests.index', 'parameters' => []];

    expect($told($this->manager))->toBe([[['localized' => ['en' => 'Campaign launched', 'ar' => 'انطلقت الحملة']], $link]])
        ->and($told($this->hr))->toBe([['Waiting on you', $link]]);
});

test('a leave notification opens the request it is about', function () {
    Notification::swap(new ChannelManager(app()));

    $this->service->sendNewRequest($this->leave, $this->requester);

    $notification = UserNotification::query()->where('notifiable_id', $this->manager->id)->where('module', 'leaves')->sole();

    $this->actingAs($this->manager)
        ->get(route('notifications.open', $notification->id))
        ->assertRedirect(route('hr.leave.show', ['leaveRequest' => $this->leave->id]));
});

test('leave and recruitment can be muted from the notification settings page by anyone', function () {
    $user = User::factory()->create();

    Livewire\Livewire::actingAs($user)->test('pages::notifications.settings')
        ->assertSeeText(__('notifications.modules.leaves'))
        ->assertSeeText(__('notifications.modules.recruitment'))
        ->call('toggleMute', 'leaves')
        ->call('toggleMute', 'recruitment')
        ->assertSet('muted', ['leaves', 'recruitment']);

    foreach (['en', 'ar'] as $locale) {
        expect(Lang::has('notifications.modules.leaves', $locale, false))->toBeTrue()
            ->and(Lang::has('notifications.modules.recruitment', $locale, false))->toBeTrue();
    }
});

test('the activity log does not offer leave and recruitment as areas: they record no activity under those names', function () {
    $this->actingAs(userWithRole('Super Admin'))->get(route('activity.index'))
        ->assertOk()
        ->assertSee("('module', 'hr-leave')", false)
        ->assertDontSee("('module', 'leaves')", false)
        ->assertDontSee("('module', 'recruitment')", false);
});
