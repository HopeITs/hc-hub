<?php

declare(strict_types=1);

namespace Modules\Tracking\Notifications\Alerts;

use App\Enums\NotificationPriority;
use App\Notifications\Alerts\AlertDispatcher;
use App\Notifications\Alerts\Scanner;
use App\Notifications\RecipientResolver;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Modules\Hr\Models\Employee;
use Modules\Tracking\Enums\ProjectStatus;
use Modules\Tracking\Models\Project;

/**
 * `proposal.responsible_inactive` (contracts.md §15.2, D-62): work still
 * named after somebody who has left HR, asking to be reassigned.
 *
 * Once per person per record. The ledger stage is the employee id, which is
 * what makes that true without storing anything: a second run claims the
 * same stage and is silent, reassigning to another departed person raises
 * again under the new id, and reassigning to somebody who is still here
 * drops the record out of the query altogether.
 *
 * HR is read once per scan into an in-memory set — never a `whereHas` across
 * connections (contracts.md §10) — and nothing about HR is written here.
 *
 * **Only open projects are scanned.** D-62 also covers the responsible on a
 * proposal location row; those rows arrive with W-34a, and the one method
 * that adds them is marked below.
 */
final class ResponsibleInactiveScanner implements Scanner
{
    private const Rule = 'responsible_inactive';

    public function __construct(
        private AlertDispatcher $dispatcher,
        private RecipientResolver $resolver,
    ) {}

    public function scan(CarbonImmutable $now): int
    {
        $inactive = $this->inactiveEmployees();

        if ($inactive->isEmpty()) {
            return 0;
        }

        // W-34a: add proposal location rows here — select the rows of live
        // proposals whose `responsible_employee_id` is in $inactive and send
        // with subject `proposal`, the same stage rule and the same message.
        return $this->projects($inactive);
    }

    /**
     * Open projects whose owner has left, one notification each.
     *
     * @param  Collection<int, Employee>  $inactive  keyed by employee id
     */
    private function projects(Collection $inactive): int
    {
        $projects = Project::query()
            ->whereIn('status', array_map(fn (ProjectStatus $status): string => $status->value, array_filter(ProjectStatus::cases(), fn (ProjectStatus $status): bool => $status->isOpen())))
            ->whereIn('user_id', $inactive->keys()->all())
            ->get(['id', 'name', 'user_id']);

        if ($projects->isEmpty()) {
            return 0;
        }

        $managers = $this->resolver->usersWithRoles(['Public Relations Manager']);
        $sent = 0;

        foreach ($projects as $project) {
            $employeeId = (int) $project->user_id;

            $payload = $this->dispatcher->payload(
                // The subject is a project, so the module that guards it is
                // the one the recipient filter must use, although the
                // catalog key lives under `proposals` (D-62 names one key
                // for both halves).
                'projects',
                NotificationPriority::Important,
                'notifications.alerts.responsible_inactive.project',
                [
                    'subject' => $project->name,
                    'person' => $inactive[$employeeId]->name,
                ],
                'project',
                $project->id,
            );

            // The stage is the person, not the date: that is «once per
            // employee per subject».
            $sent += $this->dispatcher->send(self::Rule, 'employee:'.$employeeId, $payload, $managers);
        }

        return $sent;
    }

    /**
     * Everyone HR has marked inactive, read once, keyed by id. An HR that
     * cannot be reached leaves the scan silent rather than noisy.
     *
     * @return Collection<int, Employee>
     */
    private function inactiveEmployees(): Collection
    {
        try {
            return Employee::query()
                ->where('is_active', false)
                ->get(['id', 'first_name_en', 'first_name_ar', 'last_name_en', 'last_name_ar'])
                ->keyBy('id');
        } catch (QueryException) {
            return collect();
        }
    }
}
