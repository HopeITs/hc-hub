<div x-data @open-badge-modal.window="$wire.openBadgeModal($event.detail.id)">
    <div class="flex items-center justify-between mb-6">
        <livewire:layout.breadcrumbs :title="__('general.all-badges')" :crumbs="[['label' => __('general.all-badges')]]" :showButton="false" />
    </div>

    <div class="rounded-xl border border-gray-200 bg-white/60 backdrop-blur p-3 dark:border-gray-700 dark:bg-gray-800/60">
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
                    <x-field-caption class="text-red-600 dark:text-red-400">@lang('general.employee')</x-field-caption>
                    <div class="font-medium text-gray-900 dark:text-gray-100">{{ $badge['employee'] ?? '—' }}</div>
                </div>
                <div>
                    <x-field-caption class="text-red-600 dark:text-red-400">@lang('general.type')</x-field-caption>
                    <div class="inline-flex items-center gap-2">
                        <span class="rounded-full px-2 py-0.5 text-xs font-semibold ring-1 ring-inset ring-gray-300 dark:ring-gray-600">
                            {{ $badge['type'] ?? '—' }}
                        </span>
                    </div>
                </div>
                <div>
                    <x-field-caption class="text-red-600 dark:text-red-400">@lang('general.date')</x-field-caption>
                    <div>{{ $badge['date'] ?? '—' }}</div>
                </div>
                <div>
                    <x-field-caption class="text-red-600 dark:text-red-400">{{ trans_choice('general.program', 1) }}</x-field-caption>
                    <div>{{ $badge['program'] ?? '—' }}</div>
                </div>
                <div>
                    <x-field-caption class="text-red-600 dark:text-red-400">{{ trans_choice('general.department', 1) }}</x-field-caption>
                    <div>{{ $badge['department'] ?? '—' }}</div>
                </div>
            </div>

            <div>
                <x-field-caption class="text-red-600 dark:text-red-400">@lang('general.reason')</x-field-caption>
                <div class="whitespace-pre-line rounded-md border border-gray-200 bg-gray-50 p-3 text-sm dark:border-gray-700 dark:bg-gray-900/30">
                    {{ $badge['reason'] ?? '—' }}
                </div>
            </div>

            @if(!empty($badge['is_salary']))
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <x-field-caption class="text-red-600 dark:text-red-400">@lang('general.currency')</x-field-caption>
                        <div>{{ $badge['currency'] ?? '—' }}</div>
                    </div>
                    <div>
                        <x-field-caption class="text-red-600 dark:text-red-400">@lang('general.current_salary')</x-field-caption>
                        <div>
                            @if(!empty($badge['current_salary']))
                                {{ number_format((float)$badge['current_salary'], 2) }} {{ $badge['currency'] ?? '' }}
                            @else
                                —
                            @endif
                        </div>
                    </div>
                    <div>
                        <x-field-caption class="text-red-600 dark:text-red-400">@lang('general.new-salary')</x-field-caption>
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
                <x-field-caption class="text-red-600 dark:text-red-400">@lang('general.notes')</x-field-caption>
                <div class="whitespace-pre-line rounded-md border border-gray-200 bg-gray-50 p-3 text-sm dark:border-gray-700 dark:bg-gray-900/30">
                    {{ $badge['notes'] ?? '—' }}
                </div>
            </div>
        </div>

        <div class="mt-6 flex justify-end">
            <x-modal.cancel-btn />
        </div>
    </x-modal.dialog>
</div>
