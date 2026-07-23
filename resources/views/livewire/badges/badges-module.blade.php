<div x-data @open-badge-modal.window="$wire.openBadgeModal($event.detail.id)">
    <div class="flex items-center justify-between mb-6">
        <livewire:layout.breadcrumbs :title="__('general.all-badges')" :crumbs="[['label' => __('general.all-badges')]]" :showButton="false" />
    </div>

    <div class="rounded-surface border border-neutral-200 bg-white/60 backdrop-blur p-3 dark:border-neutral-700 dark:bg-neutral-800/60">
        <livewire:badges.badges-table />
    </div>

    {{-- Modal triggered by the View button in BadgesTable --}}
    <x-modal.dialog id="badgeDetails" :title="$modalTitle" wire:model="showModal">
        <x-slot name="title">
            <span class="text-red-600 dark:text-red-400">{{ $modalTitle }}</span>
        </x-slot>

        <div class="space-y-6">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-forms.input-label required class="text-red-600 dark:text-red-400">@lang('general.employee')</x-forms.input-label>
                    <div class="font-medium text-neutral-900 dark:text-neutral-100">{{ $badge['employee'] ?? '—' }}</div>
                </div>
                <div>
                    <x-forms.input-label required class="text-red-600 dark:text-red-400">@lang('general.type')</x-forms.input-label>
                    <div class="inline-flex items-center gap-2">
                        <span class="rounded-full px-2 py-1 text-small font-semibold ring-1 ring-inset ring-neutral-300 dark:ring-neutral-600">
                            {{ $badge['type'] ?? '—' }}
                        </span>
                    </div>
                </div>
                <div>
                    <x-forms.input-label required class="text-red-600 dark:text-red-400">@lang('general.date')</x-forms.input-label>
                    <div>{{ $badge['date'] ?? '—' }}</div>
                </div>
                <div>
                    <x-forms.input-label required class="text-red-600 dark:text-red-400">{{ trans_choice('general.program', 1) }}</x-forms.input-label>
                    <div>{{ $badge['program'] ?? '—' }}</div>
                </div>
                <div>
                    <x-forms.input-label required class="text-red-600 dark:text-red-400">{{ trans_choice('general.department', 1) }}</x-forms.input-label>
                    <div>{{ $badge['department'] ?? '—' }}</div>
                </div>
            </div>

            <div>
                <x-forms.input-label required class="text-red-600 dark:text-red-400">@lang('general.reason')</x-forms.input-label>
                <div class="whitespace-pre-line rounded-control border border-neutral-200 bg-neutral-50 p-3 text-body dark:border-neutral-700 dark:bg-neutral-900/30">
                    {{ $badge['reason'] ?? '—' }}
                </div>
            </div>

            @if(!empty($badge['is_salary']))
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <x-forms.input-label required class="text-red-600 dark:text-red-400">@lang('general.currency')</x-forms.input-label>
                        <div>{{ $badge['currency'] ?? '—' }}</div>
                    </div>
                    <div>
                        <x-forms.input-label required class="text-red-600 dark:text-red-400">@lang('general.current_salary')</x-forms.input-label>
                        <div>
                            @if(!empty($badge['current_salary']))
                                {{ number_format((float)$badge['current_salary'], 2) }} {{ $badge['currency'] ?? '' }}
                            @else
                                —
                            @endif
                        </div>
                    </div>
                    <div>
                        <x-forms.input-label required class="text-red-600 dark:text-red-400">@lang('general.new-salary')</x-forms.input-label>
                        <div>
                            @if(!empty($badge['new_salary']))
                                {{ number_format((float)$badge['new_salary'], 2) }} {{ $badge['currency'] ?? '' }}
                            @else
                                —
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <div>
                <x-forms.input-label required class="text-red-600 dark:text-red-400">@lang('general.notes')</x-forms.input-label>
                <div class="whitespace-pre-line rounded-control border border-neutral-200 bg-neutral-50 p-3 text-body dark:border-neutral-700 dark:bg-neutral-900/30">
                    {{ $badge['notes'] ?? '—' }}
                </div>
            </div>
        </div>

        <div class="mt-6 flex justify-end">
            <x-modal.cancel-btn />
        </div>
    </x-modal.dialog>
</div>
