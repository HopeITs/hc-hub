@php
    $steps  = [1 => 'identity', 2 => 'terms', 3 => 'comp'];
    $active = $steps[$step] ?? 'identity';
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <livewire:layout.breadcrumbs :title="__('general.edit')" :crumbs="[
        ['label' => trans_choice('general.employee-management', 2)],
        ['label' => trans_choice('general.contracts', 2), 'url' => route('contracts.index')],
        ['label' => __('general.edit')],
    ]"/>

    <form wire:submit.prevent="submit" class="mt-2">

        <x-tab.form :activeTab="$active" wire:key="contract-edit-wizard-{{ $step }}">
            <x-tab.list>
                <x-tab.item :title="__('general.identity-org')" activeTab="identity"
                            class="pointer-events-none select-none {{ $active === 'identity' ? 'bg-primary text-white' : '' }}">
                    <svg class="w-5 h-5 mr-2 opacity-80" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zM3 18a7 7 0 1114 0H3z"
                              clip-rule="evenodd"/>
                    </svg>
                </x-tab.item>

                <x-tab.item :title="__('general.terms-status')" activeTab="terms"
                            class="{{ $maxStep < 2 ? 'opacity-50' : '' }} pointer-events-none select-none {{ $active === 'terms' ? 'bg-primary text-white' : '' }}">
                    <svg class="w-5 h-5 mr-2 opacity-80" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1z"/>
                        <path d="M18 9H2v7a2 2 0 002 2h12a2 2 0 002-2V9z"/>
                    </svg>
                </x-tab.item>

                <x-tab.item :title="__('general.Pay-schedule')" activeTab="comp"
                            class="{{ $maxStep < 3 ? 'opacity-50' : '' }} pointer-events-none select-none {{ $active === 'comp' ? 'bg-primary text-white' : '' }}">
                    <x-icons.cash class="w-5 h-5 mr-2" :title="__('general.Pay-schedule')"/>
                </x-tab.item>
            </x-tab.list>


            <div class="flex-grow w-full">
                {{-- step 1 --}}
                <x-tab.container active-tab="identity">
                    @if($step === 1)
                        <div class="mb-4 pb-3 border-b border-gray-200">
                            <h2 class="text-base font-semibold text-gray-900">@lang('general.identity-org')</h2>
                        </div>

                        @include('livewire.contract.Partials.identity-fields')

                        <div class="mt-6 flex items-center justify-end gap-3">
                            <x-primary-button type="button" wire:click="next" wire:loading.attr="disabled">
                                <span wire:loading.remove>@lang('general.next')</span>
                                <span wire:loading>@lang('general.loading')…</span>
                            </x-primary-button>
                        </div>
                    @endif
                </x-tab.container>

                {{-- step 2 --}}
                <x-tab.container active-tab="terms">
                    @if($step === 2)
                        <div class="mb-4 pb-3 border-b border-gray-200">
                            <h2 class="text-base font-semibold text-gray-900">@lang('general.terms-status')</h2>
                        </div>

                        @include('livewire.contract.Partials.terms-fields')

                        <div class="mt-6 flex items-center justify-between">
                            <x-secondary-button type="button" wire:click="prev" class="hover:bg-gray-100"
                                                wire:loading.attr="disabled">
                                @lang('general.previous')
                            </x-secondary-button>
                            <x-primary-button type="button" wire:click="next" wire:loading.attr="disabled">
                                <span wire:loading.remove>@lang('general.next')</span>
                                <span wire:loading>@lang('general.loading')…</span>
                            </x-primary-button>
                        </div>
                    @endif
                </x-tab.container>

                {{-- step 3 --}}
                <x-tab.container active-tab="comp">
                    @if($step === 3)
                        <div class="mb-4 pb-3 border-b border-gray-200">
                            <h2 class="text-base font-semibold text-gray-900">@lang('general.Pay-schedule')</h2>
                        </div>

                        {{-- IMPORTANT: match create filename (lowercase) --}}
                        @include('livewire.contract.Partials.pay-fields')

                        <div class="mt-6 flex items-center justify-between">
                            <x-secondary-button type="button" wire:click="prev" class="hover:bg-gray-100"
                                                wire:loading.attr="disabled">
                                @lang('general.previous')
                            </x-secondary-button>
                            <x-primary-button type="submit" wire:loading.attr="disabled">
                                <span wire:loading.remove>@lang('general.save')</span>
                                <span wire:loading>@lang('general.saving')…</span>
                            </x-primary-button>
                        </div>
                    @endif
                </x-tab.container>
            </div>
        </x-tab.form>
    </form>
</div>
