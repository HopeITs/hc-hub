<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationKind;
use App\Enums\NotificationPriority;
use App\Models\Activity;
use App\Models\Task;
use App\Models\User;
use App\Models\UserNotification;
use Carbon\CarbonImmutable;

/**
 * The two task workflow notices (user decision 2026-10-27): an assignee sent
 * a task for review (its creator hears, with the date), and its approver
 * sent it back with a note (its assignees hear, with the note). Both travel
 * as catalog events an administrator can switch off.
 *
 * Each notice carries the move's task.status_changed activity, so the
 * fan-out of that activity skips everyone the notice already reached; when
 * the fan-out ran first, its lone row for that person gives way to the notice.
 */
final class TaskReviewNotices
{
    public function __construct(
        private EventCatalog $catalog,
        private EmployeeRecipients $employees,
        private RecipientFilter $filter,
    ) {}

    public function submitted(User $actor, Task $task): void
    {
        $this->send('task_review.submitted', $actor, $task, [$task->created_by], ['date' => ['date' => CarbonImmutable::today()->toDateString()]]);
    }

    public function sentBack(User $actor, Task $task, string $note): void
    {
        $employeeIds = $task->taskAssignees()->pluck('employee_id')->map(fn (mixed $id): int => (int) $id)->all();

        $this->send('task_review.sent_back', $actor, $task, array_values(array_filter($this->employees->usersFor($employeeIds))), ['note' => $note]);
    }

    /**
     * @param  list<int>  $userIds
     * @param  array<string, mixed>  $params
     */
    private function send(string $event, User $actor, Task $task, array $userIds, array $params): void
    {
        $catalog = $this->catalog->effective($event);

        if ($catalog === null || ! $catalog['enabled']) {
            return;
        }

        $recipients = $this->filter->apply($userIds, $catalog['module'], $actor->id, $userIds, $userIds);
        $activityId = $this->statusChangeActivityId($actor, $task);

        if ($activityId !== null) {
            UserNotification::query()
                ->whereIn('notifiable_id', $recipients)
                ->where('notifiable_type', (new User)->getMorphClass())
                ->where('activity_id', $activityId)
                ->where('data->count', 1)
                ->delete();
        }

        User::query()->whereKey($recipients)->get()->each(fn (User $user) => $user->notify(new AppNotification(new NotificationPayload(
            kind: NotificationKind::Action,
            module: $catalog['module'],
            priority: NotificationPriority::Info,
            key: 'notifications.events.'.$event,
            params: ['actor' => $actor->name, 'subject' => $task->title, ...$params],
            subjectType: $task->getMorphClass(),
            subjectId: (int) $task->getKey(),
            actorId: $actor->id,
            activityId: $activityId,
        ))));
    }

    /**
     * The activity the move itself recorded, when recording was on.
     */
    private function statusChangeActivityId(User $actor, Task $task): ?int
    {
        $id = Activity::query()
            ->where('subject_type', $task->getMorphClass())
            ->where('subject_id', $task->getKey())
            ->where('event', 'task.status_changed')
            ->where('actor_id', $actor->id)
            ->latest('id')
            ->value('id');

        return $id === null ? null : (int) $id;
    }
}
