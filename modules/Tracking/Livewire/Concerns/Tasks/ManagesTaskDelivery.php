<?php

declare(strict_types=1);

namespace Modules\Tracking\Livewire\Concerns\Tasks;

use Flux\Flux;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Modules\Tracking\Actions\Tasks\AcceptDepartmentTask;
use Modules\Tracking\Actions\Tasks\DeliverDepartmentTask;
use Modules\Tracking\Actions\Tasks\ReturnDepartmentTask;
use Modules\Tracking\Enums\DeliveryState;
use Modules\Tracking\Models\Task;

/**
 * The media delivery moves on tasks/show: hand a deliverable over (a file, a
 * link or text), accept a delivery, or return it with a reason.
 *
 * Used by tasks/show only. The page provides taskId, the #[Computed] task()
 * and restoreProperty() (to put its status, progress and completed-date
 * fields back to the stored values), and uses WithFileUploads for
 * deliveryFiles. Authorisation stays in the three actions and the TaskPolicy
 * deliver / review abilities; each move carries the delivery state the
 * viewer saw, so a task someone else already moved is refused, not
 * overwritten. No money is read or shown here.
 *
 * @property int $taskId
 * @property-read Task $task  #[Computed] task()
 */
trait ManagesTaskDelivery
{
    /** How many files one delivery takes (DeliverDepartmentTask::MaxFiles). */
    private const MaxDeliveryFiles = 10;

    public bool $showDeliveryModal = false;

    #[Locked]
    public ?int $deliveringTaskId = null;

    #[Locked]
    public string $deliveringExpectedState = '';

    public string $deliveredOn = '';

    public string $deliveredLink = '';

    public string $deliveredText = '';

    /** @var array<int, mixed> */
    public array $deliveryFiles = [];

    public int $deliveryFileSlots = 1;

    public bool $showAcceptModal = false;

    #[Locked]
    public ?int $reviewingTaskId = null;

    #[Locked]
    public string $reviewingExpectedState = '';

    public bool $showReturnModal = false;

    public string $returnReason = '';

    public function openDelivery(): void
    {
        $task = $this->deliverableTask();

        $this->resetDeliveryForm();
        $this->deliveringTaskId = $task->id;
        $this->deliveringExpectedState = (string) $task->delivery_state?->value;
        $this->deliveredOn = today()->toDateString();
        $this->showDeliveryModal = true;
    }

    public function addDeliveryFileSlot(): void
    {
        $this->deliveryFileSlots = min(self::MaxDeliveryFiles, $this->deliveryFileSlots + 1);
    }

    public function deliver(DeliverDepartmentTask $action): void
    {
        if ($this->deliveringTaskId === null) {
            return;
        }

        $this->resetValidation();
        $task = $this->deliverableTask();

        try {
            $action->handle($task, DeliveryState::from($this->deliveringExpectedState), [
                'delivered_on' => $this->deliveredOn,
                'delivered_link' => $this->deliveredLink,
                'delivered_text' => $this->deliveredText,
            ], array_values(array_filter($this->deliveryFiles)));
        } catch (ValidationException $exception) {
            $this->showActionErrors($exception, [
                'delivered_on' => 'deliveredOn',
                'delivered_link' => 'deliveredLink',
                'delivered_text' => 'deliveredText',
                'files' => 'deliveryFiles',
            ], 'delivery');

            return;
        }

        $this->closeDeliveryModal();
        $this->refreshAfterDeliveryMove();
        Flux::toast(variant: 'success', text: __('department_tasks.delivered_toast'));
    }

    public function closeDeliveryModal(): void
    {
        $this->showDeliveryModal = false;
        $this->resetDeliveryForm();
    }

    public function confirmAccept(): void
    {
        $task = $this->deliverableTask();

        $this->resetValidation();
        $this->reviewingTaskId = $task->id;
        $this->reviewingExpectedState = (string) $task->delivery_state?->value;
        $this->showAcceptModal = true;
    }

    public function accept(AcceptDepartmentTask $action): void
    {
        if ($this->reviewingTaskId === null) {
            return;
        }

        try {
            $action->handle($this->deliverableTask(), DeliveryState::from($this->reviewingExpectedState));
        } catch (ValidationException $exception) {
            $this->closeReview();
            Flux::toast(variant: 'danger', text: (string) collect($exception->errors())->flatten()->first());
            $this->refreshAfterDeliveryMove();

            return;
        }

        $this->closeReview();
        $this->refreshAfterDeliveryMove();
        Flux::toast(variant: 'success', text: __('department_tasks.accepted_toast'));
    }

    public function confirmReturn(): void
    {
        $task = $this->deliverableTask();

        $this->resetValidation();
        $this->reviewingTaskId = $task->id;
        $this->reviewingExpectedState = (string) $task->delivery_state?->value;
        $this->returnReason = '';
        $this->showReturnModal = true;
    }

    public function returnDelivery(ReturnDepartmentTask $action): void
    {
        if ($this->reviewingTaskId === null) {
            return;
        }

        $this->resetValidation();

        try {
            $action->handle($this->deliverableTask(), DeliveryState::from($this->reviewingExpectedState), $this->returnReason);
        } catch (ValidationException $exception) {
            $this->showActionErrors($exception, ['reason' => 'returnReason'], 'returnReason');

            return;
        }

        $this->closeReview();
        $this->refreshAfterDeliveryMove();
        Flux::toast(variant: 'success', text: __('department_tasks.returned_toast'));
    }

    public function closeReview(): void
    {
        $this->showAcceptModal = false;
        $this->showReturnModal = false;
        $this->reviewingTaskId = null;
        $this->reviewingExpectedState = '';
        $this->returnReason = '';
    }

    /**
     * This page's department task, re-read through the visibility scope: a
     * task that is not a department task is a 404.
     */
    private function deliverableTask(): Task
    {
        return Task::query()->whereNotNull('department_id')->whereKey($this->taskId)->firstOrFail();
    }

    /**
     * @param  array<string, string>  $fields  action key => page property
     */
    private function showActionErrors(ValidationException $exception, array $fields, string $fallback): void
    {
        foreach ($exception->errors() as $key => $messages) {
            $field = $fields[str($key)->before('.')->toString()] ?? $fallback;

            foreach ($messages as $message) {
                $this->addError($field, $message);
            }
        }
    }

    private function resetDeliveryForm(): void
    {
        $this->resetValidation();
        $this->deliveringTaskId = null;
        $this->deliveringExpectedState = '';
        $this->deliveredOn = '';
        $this->deliveredLink = '';
        $this->deliveredText = '';
        $this->deliveryFiles = [];
        $this->deliveryFileSlots = 1;
    }

    /**
     * A delivery move changes the task's status, progress and completed date
     * (and attachments), so re-read it and put those fields back in sync.
     */
    private function refreshAfterDeliveryMove(): void
    {
        unset($this->task, $this->attachments);

        foreach (['taskStatus', 'progressPercentage', 'completedDate'] as $field) {
            $this->restoreProperty($field);
        }
    }
}
