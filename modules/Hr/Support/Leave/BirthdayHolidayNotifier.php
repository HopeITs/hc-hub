<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Leave;

use App\Enums\NotificationKind;
use App\Enums\NotificationPriority;
use App\Notifications\AppNotification;
use App\Notifications\NotificationPayload;
use App\Notifications\RecipientFilter;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Models\User;
use Modules\Hr\Models\BirthdayHolidayChoice;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\Employee;
use Modules\Hr\Support\Journeys\DeliveryLedger;
use Throwable;

/**
 * Who hears about a birthday-day choice: the direct manager to decide and HR
 * of the volunteer's city to know (HR-SYR when that city has no HR), the
 * volunteer when a day is still to be picked and once it is decided. The rules
 * of the holiday itself stay in BirthdayHoliday.
 *
 * The sentences are hr_leave.notifications.birthday_* and every one opens the
 * birthday page, as the leave notifications open the request. Each send passes
 * RecipientFilter: a mute of the `leaves` module hides the copy to HR, never
 * what is addressed to the person (the decision, their own choice).
 */
class BirthdayHolidayNotifier
{
    public function __construct(
        private readonly RecipientFilter $filter,
        private readonly DeliveryLedger $ledger,
    ) {}

    /** The volunteer asked for a day: the manager decides, city HR is told for information. */
    public function submitted(BirthdayHolidayChoice $choice): void
    {
        $choice->loadMissing('employee.city');
        $managers = $this->managersOf($choice->employee);
        $hr = $this->ledger->cityHr($choice->employee?->city_id)
            ->reject(fn (User $user): bool => $managers->contains('id', $user->id) || (int) $user->employee_id === (int) $choice->employee_id);

        DB::afterCommit(function () use ($choice, $managers, $hr): void {
            $params = ['employee' => $this->nameOf($choice->employee), 'date' => ['date' => $choice->chosen_date->toDateString()]];

            $this->send($managers, 'birthday_submitted', $params, directIds: $this->ids($managers));
            $this->send($hr, 'birthday_submitted_hr', $params);
        });
    }

    /** The manager decided: the volunteer is told. */
    public function decided(BirthdayHolidayChoice $choice): void
    {
        $volunteer = $this->accountsOf($choice->employee_id);
        $key = $choice->status === BirthdayHolidayChoice::APPROVED ? 'birthday_approved' : 'birthday_rejected';

        DB::afterCommit(fn () => $this->send($volunteer, $key, ['date' => ['date' => $choice->chosen_date->toDateString()]], directIds: $this->ids($volunteer)));
    }

    /**
     * The volunteer's birthday falls on a weekend or holiday and no day was
     * chosen yet. Returns whether it was delivered now.
     */
    public function choiceNeeded(User $user, int $holidayId, CarbonInterface $birthday, CarbonInterface $claimDay, string $ledgerKind): bool
    {
        if ($this->ledger->claim($ledgerKind, (int) $user->id, [$holidayId], $claimDay) === []) {
            return false;
        }

        try {
            $user->notify(new AppNotification($this->payload('birthday_needed', ['date' => ['date' => $birthday->toDateString()]])));
            $this->ledger->markDelivered($ledgerKind, (int) $user->id, [$holidayId], $claimDay);

            return true;
        } catch (Throwable $exception) {
            $this->ledger->release($ledgerKind, (int) $user->id, [$holidayId], $claimDay);
            report($exception);

            return false;
        }
    }

    /**
     * The people holding, now, the position the volunteer's position reports
     * to: the same rule as BirthdayHoliday::canDecide.
     *
     * @return Collection<int, User>
     */
    public function managersOf(?Employee $employee): Collection
    {
        $managerPositionId = $employee?->latestActiveContract()?->position?->direct_manager_id;

        if ($employee === null || $managerPositionId === null) {
            return collect();
        }

        $managerIds = Contract::query()->activeNow()
            ->where('position_id', $managerPositionId)
            ->where('employee_id', '<>', $employee->id)
            ->pluck('employee_id')
            ->all();

        return $this->accountsOf($managerIds);
    }

    /**
     * Active accounts of the given employee(s).
     *
     * @param  int|array<int, int>  $employeeIds
     * @return Collection<int, User>
     */
    public function accountsOf(int|array $employeeIds): Collection
    {
        return User::query()
            ->whereIn('employee_id', (array) $employeeIds ?: [0])
            ->whereHas('employee', fn (Builder $query): Builder => $query->where('is_active', true))
            ->get();
    }

    /**
     * @param  Collection<int, User>  $recipients
     * @param  array<string, mixed>  $params
     * @param  list<int>  $directIds
     */
    private function send(Collection $recipients, string $event, array $params, array $directIds = []): void
    {
        $kept = $this->filter->apply($this->ids($recipients), LeaveNotificationService::MODULE, null, $directIds);

        foreach ($recipients->filter(fn (User $user): bool => in_array((int) $user->id, $kept, true)) as $recipient) {
            try {
                $recipient->notify(new AppNotification($this->payload($event, $params)));
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function payload(string $event, array $params): NotificationPayload
    {
        return new NotificationPayload(
            kind: NotificationKind::Action,
            module: LeaveNotificationService::MODULE,
            priority: NotificationPriority::Info,
            key: 'hr_leave.notifications.'.$event,
            params: $params,
            subjectType: null,
            subjectId: null,
            link: ['route' => 'hr.leave.birthday-choices'],
        );
    }

    /**
     * @param  Collection<int, User>  $users
     * @return list<int>
     */
    private function ids(Collection $users): array
    {
        return array_values($users->map(fn (User $user): int => (int) $user->id)->all());
    }

    /**
     * @return array{localized: array{en: string, ar: string}}
     */
    private function nameOf(?Employee $employee): array
    {
        return ['localized' => [
            'en' => $employee?->nameForLocale('en') ?? '',
            'ar' => $employee?->nameForLocale('ar') ?? '',
        ]];
    }
}
