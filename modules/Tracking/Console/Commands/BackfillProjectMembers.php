<?php

declare(strict_types=1);

namespace Modules\Tracking\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Hr\Models\Employee;
use Modules\Tracking\Enums\ProjectMemberRole;
use Modules\Tracking\Enums\ProjectMemberSource;
use Modules\Tracking\Models\Milestone;
use Modules\Tracking\Models\Project;
use Modules\Tracking\Models\ProjectMember;
use Modules\Tracking\Models\TaskAssignee;

/**
 * The first fill of project teams (roadmap 2.3, D4), read from what each
 * project already records: its owner (projects.owner_employee_id) as Owner,
 * and every other employee responsible for one of its milestones or assigned
 * a task on it or on its milestones as Member. Every row is marked
 * `source = inferred` with no dates: when someone joined or left is not on
 * record, so none is made up. A person who already has an open row in that
 * role is left alone, so the command can run again safely.
 *
 * A dry run (the default) writes nothing. Either way a CSV report is saved
 * under storage/app/reports: one line per proposed row, with the evidence and
 * the cases to review by hand (an inactive employee, or a closed or archived
 * project, whose row stays open because its end is not known).
 */
class BackfillProjectMembers extends Command
{
    /**
     * @var string
     */
    protected $signature = 'projects:backfill-members
        {--apply : Write the inferred rows; without it nothing is written}';

    /**
     * @var string
     */
    protected $description = 'Fill project teams from project owners, milestones and task assignees (inferred, undated)';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $this->components->info($apply ? 'Applying the inferred project teams.' : 'Dry run: nothing will be written.');

        $projects = Project::query()->orderBy('id')->get(['id', 'name', 'status', 'owner_employee_id']);
        $evidence = $this->evidence($projects->pluck('id')->all());
        $activeEmployees = Employee::query()->where('is_active', true)->pluck('id')->map(fn (mixed $id): int => (int) $id)->flip();
        $names = Employee::query()->get(['id', 'first_name_en', 'last_name_en'])->mapWithKeys(fn (Employee $employee): array => [(int) $employee->id => trim($employee->first_name_en.' '.$employee->last_name_en)]);
        $open = ProjectMember::query()->current()->get(['project_id', 'employee_id', 'role'])
            ->mapWithKeys(fn (ProjectMember $row): array => [$row->project_id.':'.$row->employee_id.':'.$row->role->value => true]);

        $lines = [];
        $counts = ['projects' => $projects->count(), 'without_owner' => 0, 'owner_present' => 0, 'owner_added' => 0, 'member_present' => 0, 'member_added' => 0, 'review' => 0];

        DB::transaction(function () use ($projects, $evidence, $activeEmployees, $names, $open, $apply, &$lines, &$counts): void {
            foreach ($projects as $project) {
                $ownerId = $project->owner_employee_id === null ? null : (int) $project->owner_employee_id;
                $proposed = [];

                if ($ownerId === null) {
                    $counts['without_owner']++;
                } else {
                    $proposed[$ownerId] = [ProjectMemberRole::Owner, 'project owner'];
                }

                foreach ($evidence[$project->id] ?? [] as $employeeId => $reasons) {
                    if (! isset($proposed[$employeeId])) {
                        $proposed[$employeeId] = [ProjectMemberRole::Member, implode('; ', $reasons)];
                    }
                }

                foreach ($proposed as $employeeId => [$role, $reason]) {
                    $kind = $role === ProjectMemberRole::Owner ? 'owner' : 'member';
                    $present = isset($open[$project->id.':'.$employeeId.':'.$role->value]);
                    $review = [];

                    if (! $activeEmployees->has($employeeId)) {
                        $review[] = 'employee is not active';
                    }

                    if (in_array($project->status->value, ['closed', 'archived'], true)) {
                        $review[] = 'project is '.$project->status->value;
                    }

                    if ($present) {
                        $counts[$kind.'_present']++;
                    } else {
                        $counts[$kind.'_added']++;

                        if ($apply) {
                            ProjectMember::query()->create([
                                'project_id' => $project->id,
                                'employee_id' => $employeeId,
                                'role' => $role->value,
                                'starts_on' => null,
                                'ends_on' => null,
                                'source' => ProjectMemberSource::Inferred->value,
                            ]);
                        }
                    }

                    if ($review !== [] && ! $present) {
                        $counts['review']++;
                    }

                    $lines[] = [
                        $project->id, $project->name, $project->status->value, $employeeId, $names[$employeeId] ?? '',
                        $role->value, $reason, $present ? 'already on the team' : ($apply ? 'added' : 'to add'), implode('; ', $review),
                    ];
                }
            }
        });

        $path = $this->writeReport($lines, $apply);

        $this->components->twoColumnDetail('Projects', (string) $counts['projects']);
        $this->components->twoColumnDetail('Projects without an owner', (string) $counts['without_owner']);
        $this->components->twoColumnDetail('Owners already on the team', (string) $counts['owner_present']);
        $this->components->twoColumnDetail('Owners '.($apply ? 'added' : 'to add'), (string) $counts['owner_added']);
        $this->components->twoColumnDetail('Members already on the team', (string) $counts['member_present']);
        $this->components->twoColumnDetail('Members '.($apply ? 'added' : 'to add'), (string) $counts['member_added']);
        $this->components->twoColumnDetail('New rows to review by hand', (string) $counts['review']);
        $this->components->twoColumnDetail('Rows with a made-up date', '0');
        $this->components->info('Report: '.$path);

        if ($apply) {
            $missing = Project::query()
                ->whereNotNull('owner_employee_id')
                ->whereDoesntHave('members', fn ($query) => $query->current()->where('role', ProjectMemberRole::Owner->value)->whereColumn('project_members.employee_id', 'projects.owner_employee_id'))
                ->count();

            $this->components->twoColumnDetail('Projects whose owner is not on the team', (string) $missing);

            return $missing === 0 ? self::SUCCESS : self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Employees seen working on each project, with why: responsible for a
     * milestone, or assigned a task raised on the project or its milestones.
     *
     * @param  list<int>  $projectIds
     * @return array<int, array<int, list<string>>> project id => employee id => reasons
     */
    private function evidence(array $projectIds): array
    {
        $evidence = [];

        Milestone::query()->whereIn('project_id', $projectIds)->whereNotNull('responsible_employee_id')
            ->get(['project_id', 'responsible_employee_id'])
            ->groupBy(fn (Milestone $milestone): string => $milestone->project_id.':'.$milestone->responsible_employee_id)
            ->each(function ($group) use (&$evidence): void {
                $first = $group->first();
                $evidence[(int) $first->project_id][(int) $first->responsible_employee_id][] = 'responsible for '.$group->count().' milestone(s)';
            });

        $assignees = TaskAssignee::query()
            ->join('tasks', 'tasks.id', '=', 'task_assignees.task_id')
            ->leftJoin('milestones', fn ($join) => $join->on('milestones.id', '=', 'tasks.source_id')->where('tasks.source_type', 'milestone'))
            ->where(fn ($query) => $query
                ->where(fn ($inner) => $inner->where('tasks.source_type', 'project')->whereIn('tasks.source_id', $projectIds))
                ->orWhereIn('milestones.project_id', $projectIds))
            ->selectRaw("IF(tasks.source_type = 'project', tasks.source_id, milestones.project_id) as project_id, task_assignees.employee_id, COUNT(DISTINCT tasks.id) as task_count")
            ->groupByRaw("IF(tasks.source_type = 'project', tasks.source_id, milestones.project_id), task_assignees.employee_id")
            ->toBase()
            ->get();

        foreach ($assignees as $row) {
            $evidence[(int) $row->project_id][(int) $row->employee_id][] = 'assigned '.$row->task_count.' task(s)';
        }

        return $evidence;
    }

    /**
     * @param  list<list<mixed>>  $lines
     */
    private function writeReport(array $lines, bool $apply): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['project_id', 'project', 'project_status', 'employee_id', 'employee', 'role', 'evidence', 'outcome', 'review'], escape: '\\');

        foreach ($lines as $line) {
            fputcsv($handle, $line, escape: '\\');
        }

        rewind($handle);
        $path = 'reports/project-members-backfill-'.now()->format('Y-m-d-His').($apply ? '-applied' : '-dry-run').'.csv';
        Storage::disk('local')->put($path, (string) stream_get_contents($handle));
        fclose($handle);

        return Storage::disk('local')->path($path);
    }
}
