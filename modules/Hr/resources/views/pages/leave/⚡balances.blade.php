<?php

use App\Support\CsvExport;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Hr\Actions\Leave\AdjustLeaveBalance;
use Modules\Hr\Enums\LeaveTypeCases;
use Modules\Hr\Models\LeaveBalance;
use Modules\Hr\Support\Leave\LeaveContext;
use Modules\Hr\Support\Leave\LeaveEligibility;
use Modules\Hr\Support\Leave\LeaveFormat;
use Modules\Hr\Support\Leave\LeaveRequestPricing;
use Modules\Hr\Support\Settings\HrAccess;
use Symfony\Component\HttpFoundation\StreamedResponse;

/*
 * Annual leave balances of the active employees on a running contract, in
 * HR's governorates. HR adds hours or days to one, or takes them away
 * (optionally recorded as a reward or warning badge); the employee and
 * their manager are told. Exportable as CSV.
 */
new #[Layout('layouts::desk'), Title('hr_leave.titles.balances')] class extends Component {
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    public bool $showAdjustModal = false;

    #[Locked]
    public ?int $adjustingBalanceId = null;

    public string $adjustEmployeeName = '';

    public string $operation = 'sum';

    public string $unit = 'day';

    public string $amount = '';

    public bool $addBadge = false;

    public string $notes = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', LeaveBalance::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /** @return LengthAwarePaginator<int, LeaveBalance> */
    #[Computed]
    public function balances(): LengthAwarePaginator
    {
        return $this->baseQuery()->paginate(25);
    }

    public function openAdjust(int $balanceId): void
    {
        $balance = LeaveBalance::query()->with('contract:id,employee_id', 'contract.employee:id,first_name_en,first_name_ar,last_name_en,last_name_ar,city_id')->findOrFail($balanceId);
        Gate::authorize('update', $balance);

        $this->resetValidation();
        $this->reset(['operation', 'unit', 'amount', 'addBadge', 'notes']);
        $this->adjustingBalanceId = $balance->id;
        $this->adjustEmployeeName = (string) $balance->contract?->employee?->name;
        $this->showAdjustModal = true;
    }

    public function saveAdjust(): void
    {
        if ($this->adjustingBalanceId === null) {
            return;
        }

        try {
            app(AdjustLeaveBalance::class)->handle(Auth::user(), $this->adjustingBalanceId, [
                'operation' => $this->operation,
                'unit' => $this->unit,
                'amount' => $this->amount,
                'add_badge' => $this->addBadge,
                'notes' => $this->notes,
            ]);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            throw \Illuminate\Validation\ValidationException::withMessages(
                collect($exception->errors())->mapWithKeys(fn (array $messages, string $key) => [($key === 'add_badge' ? 'addBadge' : $key) => $messages])->all(),
            );
        }

        $this->closeAdjust();
        unset($this->balances);

        Flux::toast(variant: 'success', text: __('hr_leave.balance.saved'));
    }

    public function closeAdjust(): void
    {
        $this->showAdjustModal = false;
        $this->adjustingBalanceId = null;
        $this->adjustEmployeeName = '';
    }

    public function export(): StreamedResponse
    {
        Gate::authorize('viewAny', LeaveBalance::class);

        $eligibility = app(LeaveEligibility::class);
        $pricing = app(LeaveRequestPricing::class);
        $query = $this->baseQuery();

        $rows = (function () use ($query, $eligibility, $pricing): Generator {
            foreach ($query->lazy(200) as $balance) {
                $perDay = $balance->contract === null ? null : $pricing->hoursPerDayFor($balance->contract);

                yield [
                    $balance->contract?->employee?->name ?? '',
                    \Modules\Core\Models\City::nameOf($balance->contract?->employee?->city_id),
                    LeaveFormat::hours((float) $balance->deserved, $perDay),
                    LeaveFormat::hours((float) $balance->used, $perDay),
                    LeaveFormat::hours($eligibility->remainingHours($balance), $perDay),
                ];
            }
        })();

        return CsvExport::download('leave-balances-'.now()->format('Y-m-d').'.csv', [
            __('hr_leave.fields.employee'),
            __('common.city'),
            __('hr_leave.balance.deserved'),
            __('hr_leave.balance.used'),
            __('hr_leave.balance.remaining'),
        ], $rows);
    }

    /** @return Builder<LeaveBalance> */
    private function baseQuery(): Builder
    {
        $access = HrAccess::for(Auth::user());

        return LeaveBalance::query()
            ->select(['leave_balances.id', 'leave_balances.contract_id', 'leave_balances.type_id', 'leave_balances.deserved', 'leave_balances.used', 'leave_balances.amendment_offset'])
            ->where('leave_balances.type_id', LeaveTypeCases::Annual)
            ->whereHas('contract', fn (Builder $contract) => $contract->activeNow())
            ->whereHas('contract.employee', fn (Builder $employee) => $access->scopeToCities($employee->where('is_active', true))->search($this->search))
            ->with([
                'leaveType:id,name_en,name_ar,base_days',
                'contract:id,employee_id,position_id,contract_type_id,type,weekly_hours,schedule_mode,start_date,end_date',
                'contract.employee:id,first_name_en,first_name_ar,last_name_en,last_name_ar,gender,marital_status,city_id,is_active,dob,actual_dob',
                'contract.position:id,center_id,city_id,program_id,department_id,job_level_id,direct_manager_id,tech_manager_id',
                'contract.position.center:id,city_id,weekends,open_at,close_at',
            ])
            ->orderBy('leave_balances.contract_id');
    }
};
?>

@php
    $balances = $this->balances;
    $eligibility = app(\Modules\Hr\Support\Leave\LeaveEligibility::class);
    $pricing = app(\Modules\Hr\Support\Leave\LeaveRequestPricing::class);
    $muted = 'text-secondary-medium dark:text-ink-muted';
@endphp

<x-desk.register class="@container" heading-id="hr-leave-balances-heading" :title="__('hr_leave.titles.balances')" :count="$balances->total()" data-hr-leave-balances>
    <x-slot:actions>
        @can('leaves.export')
            <x-ui.button tone="action" icon="arrow-down-tray" wire:click="export" data-test="export-balances">{{ __('hr_leave.ui.export') }}</x-ui.button>
        @endcan
    </x-slot:actions>

    <x-slot:search>
        <x-desk.register-search :label="__('hr_leave.ui.search')" :placeholder="__('hr_leave.ui.search_placeholder')" wire:model.live.debounce.300ms="search" />
    </x-slot:search>

    @if ($balances->isEmpty())
        <x-ui.empty-state icon="scale" :heading="$search === '' ? __('hr_leave.balance.empty') : __('hr_leave.ui.no_matching')" />
    @else
        <x-ui.data-table :caption="__('hr_leave.titles.balances')">
            <x-slot:head>
                <th>{{ __('hr_leave.fields.employee') }}</th>
                <th class="w-36!">{{ __('common.city') }}</th>
                <th class="w-40!">{{ __('hr_leave.balance.deserved') }}</th>
                <th class="w-40!">{{ __('hr_leave.balance.used') }}</th>
                <th class="w-40!">{{ __('hr_leave.balance.remaining') }}</th>
                <th class="w-20!"><span class="sr-only">{{ __('common.actions') }}</span></th>
            </x-slot:head>

            @foreach ($balances as $balance)
                @php
                    $perDay = $balance->contract === null ? null : $pricing->hoursPerDayFor($balance->contract);
                    $remaining = $eligibility->remainingHours($balance);
                @endphp
                <tr wire:key="leave-balance-{{ $balance->id }}" data-leave-balance="{{ $balance->id }}">
                    <x-ui.data-table.cell :label="__('hr_leave.fields.employee')"><span class="font-semibold" dir="auto">{{ $balance->contract?->employee?->name }}</span></x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('common.city')">{{ \Modules\Core\Models\City::nameOf($balance->contract?->employee?->city_id) }}</x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('hr_leave.balance.deserved')">{{ \Modules\Hr\Support\Leave\LeaveFormat::hours((float) $balance->deserved, $perDay) }}</x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('hr_leave.balance.used')">{{ \Modules\Hr\Support\Leave\LeaveFormat::hours((float) $balance->used, $perDay) }}</x-ui.data-table.cell>
                    <x-ui.data-table.cell :label="__('hr_leave.balance.remaining')"><span @class(['font-semibold', 'text-status-critical' => $remaining < 0])>{{ \Modules\Hr\Support\Leave\LeaveFormat::hours($remaining, $perDay) }}</span></x-ui.data-table.cell>
                    <td class="px-3.5 py-2.5">
                        <span class="flex items-center justify-end">
                            <x-ui.button tone="ghost" size="sm" icon="adjustments-horizontal" class="size-8!" wire:click="openAdjust({{ $balance->id }})" :aria-label="__('hr_leave.balance.adjust_name', ['name' => (string) $balance->contract?->employee?->name])" data-test="adjust-balance" />
                        </span>
                    </td>
                </tr>
            @endforeach
        </x-ui.data-table>

        <div class="mt-4">
            <flux:pagination :paginator="$balances" class="text-secondary-medium! dark:text-ink-muted!" scroll-to="#hr-leave-balances-heading" />
        </div>
    @endif

    <x-ui.modal wire:model="showAdjustModal" size="sm" :title="__('hr_leave.balance.adjust_name', ['name' => $adjustEmployeeName])" submit="saveAdjust" close="closeAdjust" required-note>
        @if ($showAdjustModal)
            <div class="grid gap-y-3">
                <x-ui.form-segmented wire:model.live="operation" :label="__('hr_leave.balance.fields.operation')" :options="[
                    ['value' => 'sum', 'label' => __('hr_leave.balance.operations.sum')],
                    ['value' => 'sub', 'label' => __('hr_leave.balance.operations.sub')],
                ]" required />
                <div class="grid gap-3 sm:grid-cols-2">
                    <x-ui.form-input type="number" min="0" step="0.5" wire:model="amount" :label="__('hr_leave.balance.fields.amount')" required />
                    <x-ui.form-segmented wire:model="unit" :label="__('hr_leave.balance.fields.unit')" :options="[
                        ['value' => 'day', 'label' => __('hr_leave.unit.day')],
                        ['value' => 'hour', 'label' => __('hr_leave.unit.hour')],
                    ]" required />
                </div>
                @can('badges.create')
                    <flux:checkbox wire:model.live="addBadge" :label="$operation === 'sum' ? __('hr_leave.balance.add_reward') : __('hr_leave.balance.add_warning')" />
                @endcan
                <x-ui.form-textarea wire:model="notes" :label="__('hr_leave.balance.fields.notes')" :required="$addBadge" maxlength="5000" />
            </div>
        @endif
        <x-slot:actions>
            <x-ui.button type="submit" tone="primary" data-test="save-balance">{{ __('common.save_changes') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.modal>
</x-desk.register>
