<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Attendance;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Modules\Core\Models\User;
use Modules\Hr\Models\Employee;

/**
 * Which timesheets a print asks for: a month, optionally one city, one project or department, or
 * a few people, always within what the user may see (Roster), in the order the page shows them.
 */
class PrintSelection
{
    public function __construct(
        private readonly Roster $roster,
        private readonly Timesheet $timesheet,
        private readonly Grouping $grouping,
    ) {}

    /**
     * @return array{sheets: Collection<int, array{employee: Employee, sheet: array<string, mixed>}>, month: CarbonImmutable, draft: bool}
     */
    public function fromRequest(Request $request): array
    {
        // A print, PDF or Excel link with no month opens the month the Timesheets page opens on,
        // instead of failing validation: on a GET that meant a silent redirect "back".
        if (blank($request->query('month'))) {
            $request->merge(['month' => now()->subDays(10)->format('Y-m')]);
        }

        $data = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
            'city_id' => ['nullable', 'integer'],
            'employees' => ['nullable', 'array'],
            'employees.*' => ['integer'],
            'group_by' => ['nullable', 'in:'.Grouping::PROJECT.','.Grouping::DEPARTMENT],
            'group' => ['nullable', 'regex:/^(\d+|none)$/'],
        ]);

        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $month = CarbonImmutable::createFromFormat('!Y-m', (string) $data['month']);
        abort_unless($month instanceof CarbonImmutable, 404);

        $wanted = array_map('intval', (array) ($data['employees'] ?? []));
        $employees = $this->roster->employees($user, $month, isset($data['city_id']) ? (int) $data['city_id'] : null)
            ->when($wanted !== [], fn (Collection $all): Collection => $all->whereIn('id', $wanted));

        abort_if($employees->isEmpty(), 404);

        // One batch of reads for the whole selection, not a set per person.
        $all = $this->timesheet->forEmployees($employees, $month);

        $sheets = $employees->map(fn (Employee $employee): array => [
            'employee' => $employee,
            'sheet' => $all[$employee->id],
        ])->values();

        $by = Grouping::normalize($data['group_by'] ?? null);

        // One project or department only ("none" = people with none set).
        if (isset($data['group'])) {
            $groupId = $data['group'] === 'none' ? null : (int) $data['group'];
            $sheets = $sheets->filter(fn (array $item): bool => Grouping::groupId($item, $by) === $groupId)->values();
        }

        abort_if($sheets->isEmpty(), 404);

        // The order of the page: city, then project or department, then name.
        $sheets = $this->grouping->group($sheets, $by)
            ->flatMap(fn (array $block): Collection => $block['groups'])
            ->flatMap(fn (array $group): Collection => $group['items'])
            ->values();

        return [
            'sheets' => $sheets,
            'month' => $month,
            'draft' => $sheets->contains(fn (array $item): bool => $item['sheet']['totals']['to_decide'] > 0),
        ];
    }
}
