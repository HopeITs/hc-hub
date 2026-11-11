<?php

use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Modules\Hr\Actions\Contracts\ContractAmendmentService;
use Modules\Hr\Actions\Contracts\DraftContractAmendment;
use Modules\Hr\Livewire\Concerns\Contracts\ManagesContractForm;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\ContractAmendment;
use Modules\Hr\Models\ContractAmendmentPayTier;
use Modules\Hr\Support\Contracts\ContractAmendmentDescriber;
use Modules\Hr\Support\Employees\HrAccess;

/**
 * The amendment page (ملحق عقد): the contract form's schedule and pay panels,
 * opened on a contract in force, plus the date the change takes effect.
 * Saving records a draft — the sheet to print and sign — and changes nothing
 * on the contract yet; approving it does. Two routes: a new amendment, and
 * editing a draft (opened on the terms the draft makes).
 */
new #[Layout('layouts::desk'), Title('hr_contract_amendments.titles.form')] class extends Component {
    use ManagesContractForm;

    #[Locked]
    public ?int $draftId = null;

    #[Locked]
    public ?int $draftNumber = null;

    public string $effective_from = '';

    public string $reason = '';

    public function mount(Contract $contract, ?ContractAmendment $amendment = null): void
    {
        $this->payHidden = ! HrAccess::canViewSalary(Auth::user());
        $this->contractId = $contract->id;

        if ($amendment?->exists) {
            $amendment->setRelation('contract', $contract);
            Gate::authorize('decideAmendment', $amendment);

            $this->draftId = $amendment->id;
            $this->draftNumber = $amendment->number;
            $this->effective_from = $amendment->effective_from->toDateString();
            $this->reason = (string) $amendment->reason;
            // Opened on the terms the draft makes, so only what HR changes now moves.
            $this->fillFromContract($amendment->termsAsContract());
            $this->tiers = $this->payHidden ? [] : $amendment->payTiersAfter()
                ->get(['condition_en', 'condition_ar', 'amount'])
                ->map(fn (ContractAmendmentPayTier $tier): array => ['condition_en' => (string) $tier->condition_en, 'condition_ar' => (string) $tier->condition_ar, 'amount' => (string) $tier->amount])
                ->all();

            return;
        }

        Gate::authorize('amend', $contract);

        $this->fillFromContract($contract);
        $this->effective_from = max(today()->toDateString(), (string) ContractAmendmentService::latestAppliedDate($contract->id));
    }

    /**
     * The contract as it stands, for the "now" panel.
     */
    #[Computed]
    public function contract(): Contract
    {
        $columns = ['id', 'employee_id', 'position_id', 'contract_type_id', 'type', 'start_date', 'end_date', 'weekly_hours', 'schedule_mode',
            'work_hours_from', 'work_hours_to', 'work_hours_2_from', 'work_hours_2_to', 'work_day_from', 'work_day_to',
            'flex_days_per_week', 'flex_hours_per_week', 'flex_hours_per_month', 'flex_varies_by_requirements',
            'flex_coordinated_with_manager', 'flex_custom_en', 'flex_custom_ar'];

        if (! $this->payHidden) {
            $columns = [...$columns, 'pay_mode', 'currency', 'salary', 'salary_per', 'session_duration'];
        }

        return Contract::query()
            ->select($columns)
            ->with([
                'employee:id,first_name_en,last_name_en,first_name_ar,last_name_ar',
                'position:id,title_en,title_ar',
            ])
            ->when(! $this->payHidden, fn ($query) => $query->with(['payTiers' => fn ($tiers) => $tiers->select(['id', 'contract_id', 'condition_en', 'condition_ar', 'amount', 'sort_order'])->orderBy('sort_order')]))
            ->findOrFail($this->contractId);
    }

    /**
     * The last amendment in effect, told up front: the new one starts from
     * the terms it left and may not take effect before it.
     *
     * @return array{number: int, date: string, ago: string, count: int, summary: string}|null
     */
    #[Computed]
    public function previous(): ?array
    {
        $applied = ContractAmendment::query()
            ->where('contract_id', $this->contractId)
            ->where('status', \Modules\Hr\Enums\ContractAmendmentStatus::Applied->value)
            ->with('payTiers:id,contract_amendment_id,side,condition_en,condition_ar,amount,sort_order')
            ->orderByDesc('number')
            ->get();
        $last = $applied->first();

        if ($last === null) {
            return null;
        }

        return [
            'number' => $last->number,
            'date' => $last->effective_from->translatedFormat(__('formats.date')),
            'ago' => $last->effective_from->diffForHumans(),
            'count' => $applied->count(),
            'summary' => app(ContractAmendmentDescriber::class)->summary($last, ! $this->payHidden),
        ];
    }

    public function save(DraftContractAmendment $drafts): void
    {
        $contract = Contract::query()->findOrFail($this->contractId);
        $draft = $this->draftId === null ? null : ContractAmendment::query()->where('contract_id', $contract->id)->findOrFail($this->draftId);
        $draft?->setRelation('contract', $contract);

        $drafts->handle($contract, $draft, [...$this->form, 'effective_from' => $this->effective_from, 'reason' => $this->reason], $this->tiers, Auth::user());

        Flux::toast(variant: 'success', text: __($draft === null ? 'hr_contract_amendments.toasts.drafted' : 'hr_contract_amendments.toasts.redrafted'));

        $this->redirectRoute('hr.contracts.amendments.index', ['contract' => $contract->id], navigate: true);
    }
};
?>

@use('App\Support\BackUrl')
@use('Modules\Hr\Enums\ContractType')
@use('Modules\Hr\Support\Employees\EnumLabel')

@php
    $contract = $this->contract;
    $canViewPay = ! $payHidden;
    $previous = $this->previous;
    $employeeName = $contract->employee?->name ?? __('hr_contracts.titles.show');
    $title = $draftNumber !== null ? __('hr_contract_amendments.titles.edit', ['number' => $draftNumber]) : __('hr_contract_amendments.titles.create');
    $grid = 'grid gap-x-4 gap-y-3 @2xl/form:grid-cols-2 @5xl/form:grid-cols-3';
    $category = $this->category();
    $minDate = \Modules\Hr\Actions\Contracts\ContractAmendmentService::latestAppliedDate($contract->id);
@endphp

<section class="@container/form flex flex-col gap-5" data-amendment-form>
    <x-ui.detail-header
        :back="BackUrl::for(route('hr.contracts.amendments.index', $contract->id))"
        :breadcrumbs="[
            ['label' => __('hr_contracts.titles.index'), 'url' => route('hr.contracts.index')],
            ['label' => $employeeName, 'url' => route('hr.contracts.show', $contract->id)],
            ['label' => __('hr_contract_amendments.list_title'), 'url' => route('hr.contracts.amendments.index', $contract->id)],
            ['label' => $title],
        ]"
        :title="$title"
        :subtitle="$employeeName.($contract->position ? ' · '.$contract->position->title : '')"
    />

    @if ($previous !== null)
        <div class="rounded-panel border border-status-warn/40 bg-status-warn-soft px-4 py-3 text-sm" data-amendment-previous>
            <p class="font-semibold">{{ trans_choice('hr_contract_amendments.previous.title', $previous['count'], ['count' => $previous['count']]) }}</p>
            <p>{{ __('hr_contract_amendments.previous.last', ['number' => $previous['number'], 'date' => $previous['date'], 'ago' => $previous['ago']]) }}</p>
            @if ($previous['summary'] !== '')
                <p class="mt-1 text-secondary-medium dark:text-ink-muted">{{ $previous['summary'] }}</p>
            @endif
            <p class="mt-1">{{ __('hr_contract_amendments.previous.hint') }}</p>
        </div>
    @endif

    <div class="@container/record">
        @include('hr::pages.contracts._show-terms', ['contract' => $contract, 'canViewPay' => $canViewPay])
    </div>

    <form wire:submit="save" novalidate class="flex flex-col gap-5">
        <x-ui.panel :title="__('hr_contract_amendments.titles.form')">
            <div class="flex flex-col gap-3 p-4">
                <p class="text-sm text-secondary-medium dark:text-ink-muted">{{ __('hr_contract_amendments.hints.draft') }}</p>
                @error('terms')
                    <p class="text-sm text-status-critical" data-test="amendment-error">{{ $message }}</p>
                @enderror
                @error('amendment')
                    <p class="text-sm text-status-critical">{{ $message }}</p>
                @enderror
                <div class="{{ $grid }}">
                    <x-ui.form-input wire:model="effective_from" :label="__('hr_contract_amendments.fields.effective_from')" type="date" required
                        :min="$minDate" :max="$contract->end_date?->toDateString()"
                        :help="$minDate ? __('hr_contract_amendments.hints.min_date', ['date' => \Illuminate\Support\Carbon::parse($minDate)->translatedFormat(__('formats.date'))]) : __('hr_contract_amendments.hints.effective_from')" />
                    <x-ui.form-select wire:model="form.type" :label="__('hr_contracts.fields.type')" required>
                        @foreach ([ContractType::FullTime, ContractType::PartTime, ContractType::Freelance] as $kind)
                            @if (($category === 'service') === ($kind === ContractType::Freelance))
                                <option value="{{ $kind->value }}">{{ EnumLabel::of(ContractType::class, $kind->value) }}</option>
                            @endif
                        @endforeach
                    </x-ui.form-select>
                    <x-ui.form-textarea class="@2xl/form:col-span-2 @5xl/form:col-span-3" wire:model="reason" :label="__('hr_contract_amendments.fields.reason')" rows="2" maxlength="2000" dir="auto" />
                </div>
            </div>
        </x-ui.panel>

        @include('hr::pages.contracts._form-schedule', ['grid' => $grid])

        <p class="-mt-3 px-1 text-xs text-secondary-medium dark:text-ink-muted">{{ __('hr_contract_amendments.hints.leave') }}</p>

        @include('hr::pages.contracts._form-pay', ['grid' => $grid])

        <div class="sticky bottom-0 z-10 flex flex-wrap items-center justify-end gap-2 border-t border-panel-border bg-white/90 px-4 py-3 backdrop-blur dark:bg-surface-1/90">
            <x-ui.button :href="BackUrl::for(route('hr.contracts.amendments.index', $contract->id))" wire:navigate>{{ __('common.cancel') }}</x-ui.button>
            <x-ui.button type="submit" tone="primary" icon="check" wire:loading.attr="disabled" wire:target="save" data-test="save-amendment">{{ __('hr_contract_amendments.actions.save') }}</x-ui.button>
        </div>
    </form>
</section>
