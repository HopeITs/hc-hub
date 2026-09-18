<x-slot:title>{{ __('recruitment-workflow.pending_approvals') }}</x-slot:title>

<div class="space-y-6">
    <div>
        <h1 class="text-h2 font-semibold text-neutral-900">
            {{ __('recruitment-workflow.pending_approvals') }}
        </h1>
        <p class="mt-2 text-secondary-medium">
            {{ __('recruitment-workflow.pending_approvals_description') }}
        </p>
    </div>

    <x-table>
        <x-slot name="headings">
            <x-table.heading>{{ __('recruitment-workflow.request_number') }}</x-table.heading>
            <x-table.heading>{{ __('recruitment-workflow.requester') }}</x-table.heading>
            <x-table.heading>{{ __('recruitment-workflow.request_type') }}</x-table.heading>
            <x-table.heading>{{ __('recruitment-workflow.governorate') }}</x-table.heading>
            <x-table.heading>{{ __('recruitment-workflow.current_step') }}</x-table.heading>
            <x-table.heading>{{ __('recruitment-workflow.waiting_since') }}</x-table.heading>
            <x-table.heading />
        </x-slot>

        <x-slot name="body">
            @forelse($items as $item)
                @php
                    $request = $item->workflowInstance->request;
                    $type = \App\Enums\RecruitmentRequestType::getLocalizedName((int) $request->request_type);
                    $step = \App\Enums\WorkflowStepKey::getLocalizedName((int) $item->workflowStep->step_key);
                @endphp
                <tr wire:key="pending-recruitment-approval-{{ $item->id }}">
                    <x-table.cell>{{ $request->request_number }}</x-table.cell>
                    <x-table.cell>{{ $request->requester?->profile?->name ?? '—' }}</x-table.cell>
                    <x-table.cell>{{ $type['value'] }}</x-table.cell>
                    <x-table.cell>{{ $request->scopeCity?->name ?? '—' }}</x-table.cell>
                    <x-table.cell>
                        <span class="rounded-md bg-primary-100 px-2 py-1 text-xs font-semibold text-primary-800">
                            {{ $step['value'] }}
                        </span>
                    </x-table.cell>
                    <x-table.cell>{{ $item->workflowInstance->started_at?->format('d-m-Y H:i') ?? '—' }}</x-table.cell>
                    <x-table.cell>
                        @if(\Illuminate\Support\Facades\Route::has('recruitment.requests.show'))
                            <x-table.show-btn :href="route('recruitment.requests.show', $request->id)" />
                        @endif
                    </x-table.cell>
                </tr>
            @empty
                <x-table.no-data />
            @endforelse
        </x-slot>

        <x-slot name="pagination">
            {{ $items->links() }}
        </x-slot>
    </x-table>
</div>
