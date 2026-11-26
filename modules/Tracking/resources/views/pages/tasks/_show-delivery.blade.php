{{-- The media delivery block on ⚡show.blade.php, only for a department task that is a media deliverable: where the delivery stands, why it was returned, and the moves the viewer may make (deliver, accept, return; the policy decides, the actions re-check). No money here. Its dialogs are _show-delivery-modals. --}}
@use('Modules\Tracking\Enums\DeliveryState')
@use('Modules\Tracking\Enums\TaskKind')
@php
    $deliveryState = $this->task->delivery_state;
    $showDelivery = $this->task->department_id !== null && $this->task->kind === TaskKind::MediaDeliverable && $deliveryState !== null;
    $canDeliver = $showDelivery && in_array($deliveryState, [DeliveryState::Requested, DeliveryState::Returned], true) && auth()->user()->can('deliver', $this->task);
    $canReview = $showDelivery && $deliveryState === DeliveryState::Delivered && auth()->user()->can('review', $this->task);
    $deliveryTapSize = 'max-sm:min-h-11 touch-manipulation';
@endphp

@if ($showDelivery)
    <div class="flex flex-col gap-2 rounded-panel border border-panel-border bg-white p-3 shadow-card dark:bg-surface-1" data-test="task-delivery">
        <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
            <div class="flex items-center gap-2 text-sm">
                <span class="text-secondary-medium dark:text-ink-muted">{{ __('tasks.show.delivery') }}</span>
                <x-ui.enum-badge :case="$deliveryState" data-test="task-delivery-state" />
            </div>

            @if ($canDeliver || $canReview)
                <div class="ms-auto flex flex-wrap items-center gap-2">
                    @if ($canDeliver)
                        <x-ui.button size="sm" tone="primary" icon="arrow-up-tray" :class="$deliveryTapSize" wire:click="openDelivery" wire:loading.attr="disabled" wire:target="openDelivery" data-test="deliver-{{ $this->task->id }}">{{ __('department_tasks.deliver') }}</x-ui.button>
                    @endif
                    @if ($canReview)
                        <x-ui.button size="sm" icon="check" :class="$deliveryTapSize" wire:click="confirmAccept" data-test="accept-{{ $this->task->id }}">{{ __('department_tasks.accept') }}</x-ui.button>
                        <x-ui.button size="sm" icon="arrow-uturn-left" :class="$deliveryTapSize" wire:click="confirmReturn" data-test="return-{{ $this->task->id }}">{{ __('department_tasks.return') }}</x-ui.button>
                    @endif
                </div>
            @endif
        </div>

        @if ($deliveryState === DeliveryState::Returned && filled($this->task->returned_reason))
            <p class="text-sm text-secondary-dark dark:text-ink" data-returned-reason>
                <span class="font-semibold">{{ __('department_tasks.returned_because') }}</span> <bdi>{{ $this->task->returned_reason }}</bdi>
            </p>
        @endif
    </div>
@endif
