<?php

declare(strict_types=1);

namespace Modules\Hr\Actions\Checklists;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Modules\Hr\Models\Checklist;
use Modules\Hr\Models\Employee;
use Modules\Hr\Support\Checklists\ChecklistNotifier;
use Modules\Hr\Support\Checklists\ChecklistWorkflow;

/**
 * HR asks for a delivered item back and names who takes it. The receiver is
 * told, unless they are the person asking.
 */
final class InitiateChecklistReturn
{
    public function __construct(
        private readonly ChecklistWorkflow $workflow,
        private readonly ChecklistNotifier $notifier,
    ) {}

    /**
     * @throws ValidationException
     */
    public function handle(Employee $employee, int $checklistId, mixed $receiverId): Checklist
    {
        Gate::authorize('manage', [Checklist::class, $employee]);

        $validated = Validator::make(['receiver_id' => $receiverId], [
            'receiver_id' => ['required', 'integer', 'exists:employees,id'],
        ])->validate();

        $checklist = $this->workflow->initiateReturn($checklistId, $employee, (int) $validated['receiver_id']);

        if ($checklist->receiver_id !== Auth::user()?->employee_id) {
            $this->notifier->assigned($checklist, ChecklistNotifier::RECEIVE);
        }

        return $checklist;
    }
}
