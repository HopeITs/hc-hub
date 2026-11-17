<?php

declare(strict_types=1);

namespace Modules\Tracking\Support;

use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Models\User;
use Modules\Tracking\Models\DonorReport;
use Modules\Tracking\Models\Project;
use WeakMap;

/**
 * What a user answers for in Tracking: the projects they own and whether
 * they are named on a donor report. It lives here, not on User, so Core does
 * not depend on Tracking (D10). Remembered per user instance, since policies
 * and task visibility read it on every check.
 */
final class TrackingDuties
{
    /**
     * @var WeakMap<User, self>|null
     */
    private static ?WeakMap $duties = null;

    /**
     * @var list<int>|null
     */
    private ?array $ownedProjectIds = null;

    private ?bool $hasReportingDuties = null;

    private function __construct(public readonly User $user) {}

    public static function for(User $user): self
    {
        self::$duties ??= new WeakMap;

        return self::$duties[$user] ??= new self($user);
    }

    /**
     * Projects this user answers for: the ones they own (projects.owner_employee_id
     * is their HR employee) and every project in a program they manage.
     *
     * @return list<int>
     */
    public function ownedProjectIds(): array
    {
        if ($this->ownedProjectIds !== null) {
            return $this->ownedProjectIds;
        }

        $employeeId = $this->user->employee_id;

        if ($employeeId === null) {
            return $this->ownedProjectIds = [];
        }

        $programIds = $this->user->managedProgramIds();

        return $this->ownedProjectIds = array_values(Project::query()
            ->where(fn (Builder $query) => $query
                ->where('owner_employee_id', $employeeId)
                ->when($programIds !== [], fn (Builder $query) => $query->orWhereIn('program_id', $programIds)))
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all());
    }

    /**
     * Whether this user is named responsible on any donor report (contracts
     * §14.3), for the Donor Reports page and the Desk duties card.
     */
    public function hasReportingDuties(): bool
    {
        return $this->hasReportingDuties ??= $this->user->employee_id !== null
            && DonorReport::query()->where('responsible_employee_id', $this->user->employee_id)->exists();
    }
}
