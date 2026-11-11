<?php

declare(strict_types=1);

namespace Modules\Hr\Actions\Checklists;

use Illuminate\Support\Facades\Gate;
use Modules\Hr\Models\Checklist;
use Modules\Hr\Models\Employee;
use Modules\Hr\Support\Checklists\ChecklistWorkflow;

/**
 * HR closes a completed return cycle: the item came back, so its assignment
 * row is removed (ChecklistWorkflow::finalizeReturn). A row that has not been
 * received back cannot be deleted this way.
 */
final class DeleteChecklist
{
    public function __construct(private readonly ChecklistWorkflow $workflow) {}

    public function handle(Employee $employee, int $checklistId): void
    {
        Gate::authorize('manage', [Checklist::class, $employee]);

        $this->workflow->finalizeReturn($checklistId, $employee);
    }
}
