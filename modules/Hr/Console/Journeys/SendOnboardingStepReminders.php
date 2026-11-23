<?php

declare(strict_types=1);

namespace Modules\Hr\Console\Journeys;

use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Core\Models\User;
use Modules\Hr\Models\ChecklistItem;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\OnboardingJourney;
use Modules\Hr\Support\Journeys\DeliveryLedger;
use Modules\Hr\Support\Journeys\Notifications\JourneyNotice;
use Modules\Hr\Support\Journeys\OnboardingDeliveries;
use Modules\Hr\Support\Journeys\OnboardingJourneys;
use Throwable;

/**
 * A step of a new volunteer's onboarding journey that is past its deadline
 * reminds the person who owns it, once a week until it is done:
 *
 *  - login account, ID card -> HR of the volunteer's city (HR-SYR when none);
 *  - electronics            -> the IT person who hands them over (HR when
 *                              nobody is named or the city has none);
 *  - the manager's session  -> the direct manager (HR when nobody runs the
 *                              manager's position).
 *
 * The ledger key is the journey, the step and the week (the due date plus
 * whole weeks), so a retry or a second worker never sends the same week twice.
 * Finished journeys and former staff are skipped.
 */
class SendOnboardingStepReminders extends Command
{
    protected $signature = 'hr:send-onboarding-step-reminders';

    protected $description = 'Remind the owners of overdue onboarding steps (account, electronics, ID card, manager session), weekly until done';

    public function handle(DeliveryLedger $ledger, OnboardingJourneys $journeys, OnboardingDeliveries $deliveries): int
    {
        $today = $ledger->today();
        $sent = 0;

        OnboardingJourney::query()
            ->whereNull('completed_at')
            ->whereHas('employee', fn (Builder $query) => $query->where('is_active', true))
            ->with(['employee.city', 'employee.onboardingJourney', 'contract'])
            ->each(function (OnboardingJourney $journey) use ($ledger, $journeys, $deliveries, $today, &$sent): void {
                $employee = $journey->employee;
                $steps = collect($journeys->steps($employee, $journey) ?? [])->keyBy('key');

                foreach (['account', 'electronics', 'card'] as $key) {
                    $step = $steps->get($key);

                    if ($step === null || $step['state'] === OnboardingJourneys::STATE_DONE || ! $step['late']) {
                        continue;
                    }

                    $recipients = match ($key) {
                        'electronics' => $this->accounts($deliveries->deliverersOfMissing($employee, ChecklistItem::GROUP_ELECTRONICS))->whenEmpty(fn () => $ledger->cityHr($employee->city_id)),
                        default => $ledger->cityHr($employee->city_id),
                    };

                    $sent += $this->send($ledger, "onboarding-{$key}-overdue", $journey, $step['due'], $recipients, 'onboarding-'.$key.'-overdue', $today);
                }

                $manager = $steps->get('manager_orientation');

                if ($manager !== null && $manager['state'] !== OnboardingJourneys::STATE_DONE && $manager['late']) {
                    $recipients = $this->accounts($journeys->managerEmployees($employee));
                    $recipients = $recipients->isNotEmpty() ? $recipients : $ledger->cityHr($employee->city_id);

                    $sent += $this->send($ledger, 'onboarding-manager-orientation-overdue', $journey, $manager['due'], $recipients, 'onboarding-manager-orientation-overdue', $today);
                }
            });

        $this->components->info("Sent {$sent} onboarding reminder(s).");

        return self::SUCCESS;
    }

    /**
     * @param  Collection<int, User>  $recipients
     */
    private function send(DeliveryLedger $ledger, string $kind, OnboardingJourney $journey, ?CarbonInterface $due, Collection $recipients, string $notice, CarbonInterface $today): int
    {
        $day = OnboardingJourneys::reminderDay($due, $today);
        $employee = $journey->employee;
        $sent = 0;

        foreach ($recipients as $user) {
            if ($ledger->claim($kind, (int) $user->id, [(int) $journey->id], $day) === []) {
                continue;
            }

            try {
                $user->notify(new JourneyNotice($notice, [
                    'name' => ['en' => $employee->nameForLocale('en'), 'ar' => $employee->nameForLocale('ar')],
                    'date' => $due?->format('d-m-Y') ?? '',
                ], filled($employee->username) ? route('hr.employees.show', ['employee' => $employee->username, 'tab' => 'journeys']) : route('dashboard')));
                $ledger->markDelivered($kind, (int) $user->id, [(int) $journey->id], $day);
                $sent++;
            } catch (Throwable $exception) {
                $ledger->release($kind, (int) $user->id, [(int) $journey->id], $day);
                report($exception);
            }
        }

        return $sent;
    }

    /**
     * The login accounts of some employees.
     *
     * @param  Collection<int, Employee>  $employees
     * @return Collection<int, User>
     */
    private function accounts(Collection $employees): Collection
    {
        return User::query()->whereIn('employee_id', $employees->pluck('id')->all() ?: [0])->get();
    }
}
