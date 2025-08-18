@php use App\Enums\SalaryPer; @endphp
<div>
    @php($locale = app()->getLocale())

    {{-- Header & breadcrumbs (same component & layout as Centers) --}}
    <div class="flex justify-between items-center mb-6">
        <livewire:layout.breadcrumbs
            :title="trans_choice('general.contracts' , 2)"
            :crumbs="[
                ['label' => __('general.employee_management')],
                ['label' => trans_choice('general.contracts',2), 'route' => 'contracts.index'],
            ]"
            :showButton="true"
        />
        <div class="flex items-center gap-2">
            <x-primary-button wire:click="create">
                @lang('general.add_new')
            </x-primary-button>
        </div>
    </div>

    <x-table>
        <x-slot name="headings">
            <x-table.heading wire:click="sortBy('id')" sortable
                             :direction="$sortField === 'id' ? $sortDirection : null">
                ID
            </x-table.heading>

            <x-table.heading wire:click="sortBy('employee_name')" sortable
                             :direction="$sortField === 'employee_name' ? $sortDirection : null">
                @lang('general.employee')
            </x-table.heading>

            <x-table.heading wire:click="sortBy('position_id')" sortable
                             :direction="$sortField === 'position_id' ? $sortDirection : null">
                {{ trans_choice('general.positions', 1) }}
            </x-table.heading>

            <x-table.heading wire:click="sortBy('type_name')" sortable
                             :direction="$sortField === 'type_name' ? $sortDirection : null">
                {{ trans_choice('general.contract-type', 1) }}
            </x-table.heading>

            <x-table.heading wire:click="sortBy('dept_name')" sortable
                             :direction="$sortField === 'dept_name' ? $sortDirection : null">
                {{ trans_choice('general.department', 1) }}
            </x-table.heading>

            <x-table.heading wire:click="sortBy('level_name')" sortable
                             :direction="$sortField === 'level_name' ? $sortDirection : null">
                {{ trans_choice('general.job-level', 1) }}
            </x-table.heading>

            <x-table.heading wire:click="sortBy('start_date')" sortable
                             :direction="$sortField === 'start_date' ? $sortDirection : null">
                @lang('general.start-date')
            </x-table.heading>

            <x-table.heading wire:click="sortBy('end_date')" sortable
                             :direction="$sortField === 'end_date' ? $sortDirection : null">
                @lang('general.end-date')
            </x-table.heading>

            <x-table.heading wire:click="sortBy('yearly_leave_days')" sortable
                             :direction="$sortField === 'yearly_leave_days' ? $sortDirection : null">
                @lang('general.yearly-leave-days')
            </x-table.heading>

            <x-table.heading wire:click="sortBy('salary_per')" sortable
                             :direction="$sortField === 'salary_per' ? $sortDirection : null">
                @lang('general.salary-per')
            </x-table.heading>

            <x-table.heading>
                @lang('general.more-details')
            </x-table.heading>

            <x-table.heading wire:click="sortBy('is_active')" sortable
                             :direction="$sortField === 'is_active' ? $sortDirection : null">
                @lang('general.is-active')
            </x-table.heading>

            <x-table.heading />
        </x-slot>

        <x-slot name="body">
            @forelse($items as $item)
                <tr wire:key="contract-{{ $item->id }}">
                    <x-table.cell class="whitespace-nowrap">
                        {{ $item->id }}
                    </x-table.cell>

                    <x-table.cell class="max-w-64 truncate"
                                  title="{{ optional($item->employee)->getTranslation('name', $locale) }}">
                        {{ optional($item->employee)->getTranslation('name', $locale) ?? '-' }}
                    </x-table.cell>

                    <x-table.cell class="max-w-56 truncate"
                                  title="{{ $item->position?->requiredPosition?->getTranslation('name', $locale) ?? ('#'.$item->position_id) }}">
                        {{ $item->position?->requiredPosition?->getTranslation('name', $locale) ?? ('#'.$item->position_id) }}
                    </x-table.cell>

                    <x-table.cell class="max-w-56 truncate"
                                  title="{{ optional($item->contractType)->getTranslation('name', $locale) }}">
                        {{ optional($item->contractType)->getTranslation('name', $locale) ?? '-' }}
                    </x-table.cell>

                    <x-table.cell class="max-w-56 truncate" title="{{ $item->department?->name }}">
                        {{ $item->department?->name ?? '-' }}
                    </x-table.cell>

                    <x-table.cell class="max-w-56 truncate"
                                  title="{{ optional($item->jobLevel)->getTranslation('name', $locale) }}">
                        {{ optional($item->jobLevel)->getTranslation('name', $locale) ?? '-' }}
                    </x-table.cell>

                    <x-table.cell class="whitespace-nowrap">
                        {{ optional($item->start_date)->toDateString() ?? '-' }}
                    </x-table.cell>
                    <x-table.cell class="whitespace-nowrap">
                        {{ optional($item->end_date)->toDateString() ?? '-' }}
                    </x-table.cell>

                    <x-table.cell class="whitespace-nowrap">
                        {{ $item->yearly_leave_days ?? '-' }}
                    </x-table.cell>

                    <x-table.cell class="whitespace-nowrap">
                        @php($per = \App\Enums\SalaryPer::getLocalizedName((int) $item->salary_per))
                        <x-badge :color="$per['color']">{{ $per['value'] }}</x-badge>
                    </x-table.cell>


                    <x-table.cell class="whitespace-nowrap">
                        <button type="button"
                                wire:click="$dispatch('openDetails', { id: {{ $item->id }} })"
                                class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded border text-xs
                           bg-white text-gray-700 border-gray-200 hover:bg-gray-50">
                            @lang('general.more')
                        </button>
                    </x-table.cell>

                    <x-table.cell-status
                        :active="$item->is_active"
                        :model-id="$item->id"
                        wire-event="confirmToggleItemStatus"
                    />

                    <x-table.cell class="whitespace-nowrap">
                        <x-table.edit-btn wire:click="edit({{ $item->id }})"/>
                        <x-table.delete-btn :modelId="$item->id"/>
                    </x-table.cell>
                </tr>
            @empty
                <x-table.no-data/>
            @endforelse
        </x-slot>


        <x-slot name="pagination">
            {{ $items->onEachSide(1)->links() }}
        </x-slot>
    </x-table>

    <x-modal.dialog id="showModal" :title="$modalTitle" wire:model.defer="showModal" wire:ignore.self>
        {{-- Alpine state for tabs --}}
        <div x-data="{ tab: 'identity' }" class="flex flex-col">

            {{-- Top bar: Active toggle --}}
            <div class="mb-3 flex items-center justify-between rounded-xl border bg-white px-4 py-2">
                <div class="text-sm text-gray-600">
                    @lang('general.employee') · {{ trans_choice('general.contract-type',1) }} · {{ trans_choice('general.department',1) }}
                </div>
                <label class="inline-flex items-center gap-2">
                    <input type="checkbox" wire:model="item.is_active" class="rounded border-gray-300">
                    <span class="text-sm">@lang('general.is-active')</span>
                </label>
            </div>

            {{-- Tab list --}}
            <div class="sticky top-0 z-10 mb-3 rounded-xl border bg-white p-1">
                <div class="flex flex-wrap gap-1" role="tablist">
                    <button type="button"
                            @click="tab='identity'"
                            :class="tab==='identity' ? 'bg-primary/10 text-primary' : 'text-gray-700 hover:bg-gray-50'"
                            class="group inline-flex items-center gap-2 rounded-lg px-3 py-2 transition">
                        <i class="ti ti-id-badge"></i>
                        <span>@lang('general.identity-org')</span>
                    </button>

                    <button type="button"
                            @click="tab='terms'"
                            :class="tab==='terms' ? 'bg-primary/10 text-primary' : 'text-gray-700 hover:bg-gray-50'"
                            class="group inline-flex items-center gap-2 rounded-lg px-3 py-2 transition">
                        <i class="ti ti-calendar-stats"></i>
                        <span>@lang('general.terms-status')</span>
                    </button>

                    <button type="button"
                            @click="tab='comp'"
                            :class="tab==='comp' ? 'bg-primary/10 text-primary' : 'text-gray-700 hover:bg-gray-50'"
                            class="group inline-flex items-center gap-2 rounded-lg px-3 py-2 transition">
                        <i class="ti ti-currency-dollar"></i>
                        <span>@lang('general.compensation-schedule')</span>
                    </button>
                </div>
            </div>

            {{-- Scrollable content area --}}
            <form wire:submit.prevent="save" class="max-h-[70vh] overflow-y-auto space-y-5 px-0.5">

                {{-- TAB: Identity & Org --}}
                <section x-show="tab==='identity'" x-transition.opacity.duration.150ms
                         class="rounded-2xl border bg-white p-4 shadow-sm">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        {{-- Employee --}}
                        <div>
                            <x-forms.input-label for="employee_id">@lang('general.employee')</x-forms.input-label>
                            <x-forms.select-input id="employee_id" wire:model="item.employee_id">
                                <option value="">{{ __('general.select') }}</option>
                                @foreach($employees as $e)
                                    <option value="{{ $e->id }}">{{ $e->getTranslation('name', $locale) }}</option>
                                @endforeach
                            </x-forms.select-input>
                            <x-error-span>{{ $errors->first('item.employee_id') }}</x-error-span>
                        </div>

                        {{-- Position --}}
                        <div>
                            <x-forms.input-label for="position_id">{{ trans_choice('general.positions', 1) }}</x-forms.input-label>
                            <x-forms.select-input id="position_id" wire:model="item.position_id">
                                <option value="">{{ __('general.select') }}</option>
                                @foreach($positions as $p)
                                    <option value="{{ $p->id }}">#{{ $p->id }} — {{ $p->center?->name ?? ('Center #'.$p->center_id) }}</option>
                                @endforeach
                            </x-forms.select-input>
                            <x-error-span>{{ $errors->first('item.position_id') }}</x-error-span>
                        </div>

                        {{-- Contract type --}}
                        <div>
                            <x-forms.input-label for="contract_type_id">{{ trans_choice('general.contract-type',1) }}</x-forms.input-label>
                            <x-forms.select-input id="contract_type_id" wire:model="item.contract_type_id">
                                <option value="">{{ __('general.select') }}</option>
                                @foreach($types as $t)
                                    <option value="{{ $t->id }}">{{ $t->getTranslation('name', $locale) }}</option>
                                @endforeach
                            </x-forms.select-input>
                            <x-error-span>{{ $errors->first('item.contract_type_id') }}</x-error-span>
                        </div>

                        {{-- Department --}}
                        <div>
                            <x-forms.input-label for="department_id">{{ trans_choice('general.department',1) }}</x-forms.input-label>
                            <x-forms.select-input id="department_id" wire:model="item.department_id">
                                <option value="">{{ __('general.select') }}</option>
                                @foreach($departments as $d)
                                    <option value="{{ $d->id }}">{{ $d->name }}</option>
                                @endforeach
                            </x-forms.select-input>
                            <x-error-span>{{ $errors->first('item.department_id') }}</x-error-span>
                        </div>

                        {{-- Job level (full width on md) --}}
                        <div class="md:col-span-2">
                            <x-forms.input-label for="job_level_id">{{ trans_choice('general.job-level',1) }}</x-forms.input-label>
                            <x-forms.select-input id="job_level_id" wire:model="item.job_level_id">
                                <option value="">{{ __('general.select') }}</option>
                                @foreach($levels as $l)
                                    <option value="{{ $l->id }}">{{ $l->getTranslation('name', $locale) }}</option>
                                @endforeach
                            </x-forms.select-input>
                            <x-error-span>{{ $errors->first('item.job_level_id') }}</x-error-span>
                        </div>

                    </div>
                </section>

                {{-- TAB: Terms & Status --}}
                <section x-show="tab==='terms'" x-transition.opacity.duration.150ms
                         class="rounded-2xl border bg-white p-4 shadow-sm">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <x-forms.input-label for="start_date">@lang('general.start-date')</x-forms.input-label>
                            <x-forms.input id="start_date" type="date" wire:model.defer="item.start_date"/>
                            <x-error-span>{{ $errors->first('item.start_date') }}</x-error-span>
                        </div>
                        <div>
                            <x-forms.input-label for="end_date">@lang('general.end-date')</x-forms.input-label>
                            <x-forms.input id="end_date" type="date" wire:model.defer="item.end_date"/>
                            <x-error-span>{{ $errors->first('item.end_date') }}</x-error-span>
                        </div>
                        <div>
                            <x-forms.input-label for="weekly_hours">@lang('general.weekly-hours')</x-forms.input-label>
                            <x-forms.input id="weekly_hours" type="number" min="0" wire:model.defer="item.weekly_hours" placeholder="40"/>
                            <x-error-span>{{ $errors->first('item.weekly_hours') }}</x-error-span>
                        </div>

                        <div>
                            <x-forms.input-label for="yearly_leave_days">@lang('general.yearly-leave-days')</x-forms.input-label>
                            <x-forms.input id="yearly_leave_days" type="number" min="0" wire:model.defer="item.yearly_leave_days" placeholder="12"/>
                            <x-error-span>{{ $errors->first('item.yearly_leave_days') }}</x-error-span>
                        </div>

                        <div>
                            <x-forms.input-label for="terminated_at">@lang('general.terminated-at')</x-forms.input-label>
                            <x-forms.input id="terminated_at" type="date" wire:model.defer="item.terminated_at"/>
                            <x-error-span>{{ $errors->first('item.terminated_at') }}</x-error-span>
                        </div>

                        <div class="md:col-span-2">
                            <x-forms.input-label for="reason_of_termination">@lang('general.reason-of-termination')</x-forms.input-label>
                            <x-forms.textarea id="reason_of_termination" rows="2"
                                              wire:model.defer="item.reason_of_termination"
                                              placeholder="{{ __('general.optional') }}"/>
                            <x-error-span>{{ $errors->first('item.reason_of_termination') }}</x-error-span>
                        </div>
                    </div>
                </section>

                {{-- TAB: Compensation --}}
                <section x-show="tab==='comp'" x-transition.opacity.duration.150ms
                         class="rounded-2xl border bg-white p-4 shadow-sm">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">

                        {{-- Currency + Salary (RTL/LTR aware) --}}
                        <div class="md:col-span-2">
                            <x-forms.input-label for="salary">@lang('general.salary')</x-forms.input-label>
                            <div class="flex">
                                <x-forms.select-input wire:model="item.currency"
                                                      class="w-28 ltr:rounded-r-none rtl:rounded-l-none ltr:border-r-0 rtl:border-l-0">
                                    <option value="0">UNK</option>
                                    <option value="1">SYP</option>
                                    <option value="2">USD</option>
                                    <option value="3">EUR</option>
                                </x-forms.select-input>
                                <x-forms.input id="salary" type="number" step="0.01" min="0"
                                               wire:model.defer="item.salary"
                                               class="w-full ltr:rounded-l-none rtl:rounded-r-none" placeholder="0.00"/>
                            </div>
                            <x-error-span>{{ $errors->first('item.currency') ?: $errors->first('item.salary') }}</x-error-span>
                        </div>

                        {{-- Salary Per segmented --}}
                        <div>
                            <x-forms.input-label for="salary_per">@lang('general.salary-per')</x-forms.input-label>
                            <div class="inline-flex items-center gap-1 rounded-lg border bg-gray-50 p-1">
                                <label class="cursor-pointer">
                                    <input type="radio" class="peer sr-only" wire:model="item.salary_per" value="1">
                                    <span class="px-2.5 py-1 text-xs rounded-md text-gray-600
                                             peer-checked:bg-white peer-checked:text-gray-900 peer-checked:shadow">
                                    @lang('general.month')
                                </span>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" class="peer sr-only" wire:model="item.salary_per" value="2">
                                    <span class="px-2.5 py-1 text-xs rounded-md text-gray-600
                                             peer-checked:bg-white peer-checked:text-gray-900 peer-checked:shadow">
                                    @lang('general.hour')
                                </span>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" class="peer sr-only" wire:model="item.salary_per" value="3">
                                    <span class="px-2.5 py-1 text-xs rounded-md text-gray-600
                                             peer-checked:bg-white peer-checked:text-gray-900 peer-checked:shadow">
                                    @lang('general.session')
                                </span>
                                </label>
                            </div>
                            <x-error-span>{{ $errors->first('item.salary_per') }}</x-error-span>
                        </div>
                    </div>
                </section>

                {{-- Wizard nav inside the scrollable area --}}
                <div class="flex items-center justify-between pt-1">
                    <div class="flex gap-2">
                        <button type="button" class="btn btn-light"
                                @click="tab = (tab === 'terms' ? 'identity' : (tab === 'comp' ? 'terms' : 'identity'))">
                            @lang('general.prev')
                        </button>
                        <button type="button" class="btn btn-light"
                                @click="tab = (tab === 'identity' ? 'terms' : (tab === 'terms' ? 'comp' : 'comp'))">
                            @lang('general.next')
                        </button>
                    </div>

                    <div class="flex gap-2">
                        <x-modal.cancel-btn/>
                        <x-primary-button type="submit" class="!w-auto">
                            @lang('general.save')
                        </x-primary-button>
                    </div>
                </div>
            </form>
        </div>
    </x-modal.dialog>


</div>
