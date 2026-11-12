<?php

use App\Rules\SecureUpload;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Modules\Hr\Actions\Contracts\ContractAmendmentCannotApply;
use Modules\Hr\Actions\Contracts\ContractAmendmentService;
use Modules\Hr\Http\Controllers\Contracts\ContractAmendmentSignedController;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\ContractAmendment;
use Modules\Hr\Support\Contracts\ContractAmendmentDescriber;
use Modules\Hr\Support\Employees\HrAccess;
use Modules\Hr\Support\Employees\HrMedia;

/**
 * A contract's amendments (ملحق عقد), newest first: what each one changed,
 * and the steps of the one in flight — print the sheet, approve once signed,
 * edit or discard a draft, cancel one approved for a date still to come,
 * attach the signed copy and download it.
 */
new #[Layout('layouts::desk'), Title('hr_contract_amendments.titles.index')] class extends Component {
    use WithFileUploads;

    #[Locked]
    public int $contractId;

    /** @var array<int|string, mixed> one upload slot per amendment, keyed by its id */
    public array $signedFiles = [];

    #[Locked]
    public bool $canViewPay = false;

    #[Locked]
    public ?int $pendingId = null;

    #[Locked]
    public string $pendingAction = '';

    public bool $showConfirm = false;

    public function mount(Contract $contract): void
    {
        Gate::authorize('view', $contract);

        $this->contractId = $contract->id;
        $this->canViewPay = HrAccess::canViewSalary(Auth::user());
    }

    #[Computed]
    public function contract(): Contract
    {
        return Contract::query()
            ->select(['id', 'employee_id', 'position_id', 'contract_type_id', 'type', 'is_active', 'start_date', 'end_date', 'terminated_at', 'termination_reason_id'])
            ->with([
                'employee:id,first_name_en,last_name_en,first_name_ar,last_name_ar,username',
                'position:id,title_en,title_ar',
            ])
            ->findOrFail($this->contractId);
    }

    /**
     * @return Collection<int, ContractAmendment>
     */
    #[Computed]
    public function amendments(): Collection
    {
        $contract = $this->contract;

        // Numbers follow effective dates (a new one may not take effect
        // before the last one in effect), so the number orders them.
        return ContractAmendment::query()
            ->where('contract_id', $contract->id)
            ->with('payTiers:id,contract_amendment_id,side,condition_en,condition_ar,amount,sort_order')
            ->orderByDesc('number')
            ->get()
            ->each(fn (ContractAmendment $amendment) => $amendment->setRelation('contract', $contract));
    }

    /**
     * The amendments that have a signed copy on file, by id.
     *
     * @return array<int, true>
     */
    #[Computed]
    public function signedCopies(): array
    {
        $ids = array_values($this->amendments->map(fn (ContractAmendment $amendment): int => (int) $amendment->id)->all());

        return HrMedia::for(HrMedia::CONTRACT_AMENDMENT, $ids, ContractAmendmentSignedController::COLLECTION)
            ->mapWithKeys(fn (object $media): array => [(int) $media->model_id => true])
            ->all();
    }

    /**
     * A file chosen in an amendment's upload slot is its signed copy; a new
     * one replaces the one on file.
     */
    public function updatedSignedFiles(mixed $value, string|int $key): void
    {
        $amendment = $this->amendment((int) $key);
        Gate::authorize('attachSignedAmendment', $amendment);

        try {
            $this->validate([
                "signedFiles.{$key}" => ['required', 'file', 'max:10240', 'mimes:'.implode(',', ContractAmendmentSignedController::ALLOWED_EXTENSIONS), new SecureUpload],
            ], [], ["signedFiles.{$key}" => __('hr_contract_amendments.signed.label')]);
        } catch (ValidationException $exception) {
            unset($this->signedFiles[$key]);

            throw $exception;
        }

        $file = $this->signedFiles[$key];

        if (! $file instanceof TemporaryUploadedFile) {
            return;
        }

        $previous = HrMedia::for(HrMedia::CONTRACT_AMENDMENT, (int) $amendment->id, ContractAmendmentSignedController::COLLECTION);

        HrMedia::store(
            $file,
            HrMedia::CONTRACT_AMENDMENT,
            (int) $amendment->id,
            ContractAmendmentSignedController::COLLECTION,
            displayName: 'amendment-'.$amendment->contract_id.'-'.$amendment->number,
        );

        $previous->each(fn (object $media) => HrMedia::delete((int) $media->id));

        unset($this->signedFiles[$key], $this->signedCopies);

        Flux::toast(variant: 'success', text: __('hr_contract_amendments.toasts.signed_attached'));
    }

    public function askApprove(int $amendmentId): void
    {
        $amendment = $this->amendment($amendmentId);
        Gate::authorize('decideAmendment', $amendment);

        $this->pendingId = $amendment->id;
        $this->pendingAction = 'approve';
        $this->showConfirm = true;
    }

    public function askDiscard(int $amendmentId): void
    {
        $amendment = $this->amendment($amendmentId);
        Gate::authorize('discardAmendment', $amendment);

        $this->pendingId = $amendment->id;
        $this->pendingAction = 'discard';
        $this->showConfirm = true;
    }

    public function closeConfirm(): void
    {
        $this->showConfirm = false;
        $this->pendingId = null;
        $this->pendingAction = '';
    }

    public function confirmPending(ContractAmendmentService $amendments): void
    {
        if ($this->pendingId === null) {
            return;
        }

        $amendment = $this->amendment($this->pendingId);
        $action = $this->pendingAction;
        $this->closeConfirm();

        try {
            if ($action === 'approve') {
                Gate::authorize('decideAmendment', $amendment);
                $amendment = $amendments->approve($amendment, Auth::user());
                Flux::toast(variant: 'success', text: __($amendment->isApplied() ? 'hr_contract_amendments.toasts.applied' : 'hr_contract_amendments.toasts.approved'));
            } elseif ($action === 'discard') {
                Gate::authorize('discardAmendment', $amendment);
                $amendments->discard($amendment);
                Flux::toast(variant: 'success', text: __('hr_contract_amendments.toasts.discarded'));
            }
        } catch (ValidationException $exception) {
            Flux::toast(variant: 'danger', text: (string) collect($exception->errors())->flatten()->first());
        } catch (ContractAmendmentCannotApply $exception) {
            // Approval is kept; the daily run, or HR after fixing the cause, applies it.
            Flux::toast(variant: 'danger', text: $exception->getMessage());
        }

        unset($this->amendments);
    }

    private function amendment(int $id): ContractAmendment
    {
        $amendment = ContractAmendment::query()->where('contract_id', $this->contractId)->findOrFail($id);
        $amendment->setRelation('contract', Contract::query()->findOrFail($this->contractId));

        return $amendment;
    }
};
?>

@use('App\Enums\StatusTone')
@use('App\Support\BackUrl')
@use('Modules\Hr\Enums\ContractAmendmentStatus')

@php
    $contract = $this->contract;
    $amendments = $this->amendments;
    $describer = app(ContractAmendmentDescriber::class);
    $employeeName = $contract->employee?->name ?? __('hr_contracts.titles.show');
    // The amendment whose terms the contract carries now; older ones in effect are superseded.
    $currentId = $amendments->first(fn ($amendment) => $amendment->isApplied())?->id;
    $pending = $pendingId === null ? null : $amendments->firstWhere('id', $pendingId);
    $signedCopies = $this->signedCopies;
@endphp

<section class="@container/record flex flex-col gap-5" data-contract-amendments="{{ $contract->id }}">
    <x-ui.detail-header
        :back="BackUrl::for(route('hr.contracts.show', $contract->id))"
        :breadcrumbs="[
            ['label' => __('hr_contracts.titles.index'), 'url' => route('hr.contracts.index')],
            ['label' => $employeeName, 'url' => route('hr.contracts.show', $contract->id)],
            ['label' => __('hr_contract_amendments.list_title')],
        ]"
        :title="__('hr_contract_amendments.titles.index')"
        :subtitle="$employeeName.($contract->position ? ' · '.$contract->position->title : '')"
    >
        <x-slot:actions>
            <div class="flex flex-wrap items-center gap-2">
                @can('export', $contract)
                    <x-ui.button icon="document-text" :href="route('hr.contracts.legal', $contract->id)" target="_blank" data-test="print-contract-legal">{{ __('hr_contract_amendments.actions.print_legal') }}</x-ui.button>
                @endcan
                @can('amend', $contract)
                    <x-ui.button tone="primary" icon="plus" :href="route('hr.contracts.amend', $contract->id)" wire:navigate data-test="add-amendment">{{ __('hr_contract_amendments.actions.add') }}</x-ui.button>
                @endcan
            </div>
        </x-slot:actions>
    </x-ui.detail-header>

    @if ($amendments->isEmpty())
        <x-ui.panel>
            <p class="p-4 text-sm text-secondary-medium dark:text-ink-muted">{{ __('hr_contract_amendments.none') }}</p>
        </x-ui.panel>
    @endif

    @foreach ($amendments as $amendment)
        @php
            $status = $amendment->statusEnum();
            $superseded = $amendment->isApplied() && $amendment->id !== $currentId;
            $tone = match (true) {
                $superseded => StatusTone::Neutral,
                $status === ContractAmendmentStatus::Applied => StatusTone::Good,
                $status === ContractAmendmentStatus::Approved => StatusTone::Active,
                default => StatusTone::Warn,
            };
            $terms = $describer->terms($amendment, $canViewPay);
            $hasSigned = isset($signedCopies[$amendment->id]);
        @endphp

        <x-ui.panel wire:key="amendment-{{ $amendment->id }}" data-amendment="{{ $amendment->id }}">
            <x-slot:toolbar>
                <div class="flex flex-wrap items-center gap-2">
                    @can('exportAmendment', $amendment)
                        <x-ui.button size="sm" icon="printer" :href="route('hr.contracts.amendments.print', [$contract->id, $amendment->id])" target="_blank">{{ __('hr_contract_amendments.actions.print') }}</x-ui.button>
                    @endcan
                    @if ($hasSigned)
                        @can('exportAmendment', $amendment)
                            <x-ui.button size="sm" icon="arrow-down-tray" :href="route('hr.contracts.amendments.signed', [$contract->id, $amendment->id])" data-test="download-signed-amendment">{{ __('hr_contract_amendments.actions.download_signed') }}</x-ui.button>
                        @endcan
                    @endif
                    @can('attachSignedAmendment', $amendment)
                        <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-panel-border px-3 py-1.5 text-sm font-semibold" data-test="attach-signed-amendment">
                            <flux:icon.arrow-up-tray class="size-4" />
                            {{ $hasSigned ? __('hr_contract_amendments.actions.replace_signed') : __('hr_contract_amendments.actions.attach_signed') }}
                            <input type="file" class="sr-only" wire:model="signedFiles.{{ $amendment->id }}" accept=".{{ implode(',.', ContractAmendmentSignedController::ALLOWED_EXTENSIONS) }}">
                        </label>
                    @endcan
                    @can('decideAmendment', $amendment)
                        <x-ui.button size="sm" icon="pencil-square" :href="route('hr.contracts.amendments.edit', [$contract->id, $amendment->id])" wire:navigate>{{ __('hr_contract_amendments.actions.edit') }}</x-ui.button>
                        <x-ui.button size="sm" tone="primary" icon="check" wire:click="askApprove({{ $amendment->id }})" data-test="approve-amendment">{{ __('hr_contract_amendments.actions.approve') }}</x-ui.button>
                    @endcan
                    @can('discardAmendment', $amendment)
                        <x-ui.button size="sm" tone="ghost" icon="trash" wire:click="askDiscard({{ $amendment->id }})" data-test="discard-amendment">{{ $amendment->isDraft() ? __('hr_contract_amendments.actions.discard') : __('hr_contract_amendments.actions.cancel') }}</x-ui.button>
                    @endcan
                </div>
            </x-slot:toolbar>

            <div class="flex flex-col gap-3 p-4">
                @error('signedFiles.'.$amendment->id) <p class="text-sm text-status-critical" data-test="signed-amendment-error">{{ $message }}</p> @enderror
                <p wire:loading wire:target="signedFiles.{{ $amendment->id }}" class="text-sm text-secondary-medium dark:text-ink-muted">{{ __('hr_contract_amendments.signed.uploading') }}</p>

                <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                    <span class="text-base font-bold">{{ __('hr_contract_amendments.number', ['number' => $amendment->number]) }}</span>
                    <x-ui.enum-badge :case="$status" :tone="$tone" :label="$superseded ? __('hr_contract_amendments.status.superseded') : $status->label()" />
                    <span class="text-sm tabular-nums text-secondary-medium dark:text-ink-muted">{{ __('hr_contract_amendments.effective_from_summary', ['date' => $amendment->effective_from->translatedFormat(__('formats.date'))]) }}</span>
                    @if ($hasSigned)
                        <span class="inline-flex items-center gap-1 text-sm text-secondary-medium dark:text-ink-muted" data-test="signed-amendment-on-file"><flux:icon.paper-clip class="size-4" />{{ __('hr_contract_amendments.signed.on_file') }}</span>
                    @endif
                </div>

                @if (filled($amendment->reason))
                    <div class="rounded-md border-s-4 border-secondary-dark bg-secondary-soft/50 px-3 py-2 dark:border-ink dark:bg-surface-2">
                        <p class="text-xs font-semibold text-secondary-medium dark:text-ink-muted">{{ __('hr_contract_amendments.reason_label') }}</p>
                        <p dir="auto" class="whitespace-pre-line text-sm">{{ $amendment->reason }}</p>
                    </div>
                @endif

                @if ($terms !== [])
                    {{-- Every term after this amendment, so it reads on its own; what changed is outlined, with what it replaced beneath. --}}
                    <dl class="grid gap-2 @2xl/record:grid-cols-2 @5xl/record:grid-cols-3">
                        @foreach ($terms as $term)
                            <div @class([
                                'min-w-0 rounded-md px-3 py-2',
                                'border border-secondary-dark bg-white dark:border-ink dark:bg-surface-1' => $term['changed'],
                                'border border-transparent bg-secondary-soft/50 dark:bg-surface-2' => ! $term['changed'],
                            ])>
                                <dt class="flex items-center justify-between gap-2 text-xs text-secondary-medium dark:text-ink-muted">
                                    <span class="truncate">{{ $term['label'] }}</span>
                                    @if ($term['changed'])
                                        <span class="shrink-0 font-semibold text-secondary-dark dark:text-ink">{{ __('hr_contract_amendments.changed') }}</span>
                                    @endif
                                </dt>
                                <dd @class(['text-sm tabular-nums', 'font-semibold' => $term['changed'], 'text-secondary-medium dark:text-ink-muted' => ! $term['changed']])>{{ $term['after'] }}</dd>
                                <dd class="text-xs tabular-nums text-secondary-medium dark:text-ink-muted">
                                    @if ($term['changed'])
                                        {{ $term['before'] !== '' ? __('hr_contract_amendments.terms.was', ['value' => $term['before']]) : __('hr_contract_amendments.newly_set') }}
                                    @else
                                        {{ __('hr_contract_amendments.unchanged') }}
                                    @endif
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                @endif
            </div>
        </x-ui.panel>
    @endforeach

    <x-ui.confirm-modal
        wire:model="showConfirm"
        close="closeConfirm"
        :tone="$pendingAction === 'approve' ? 'neutral' : 'danger'"
        :title="match (true) {
            $pendingAction === 'approve' => __('hr_contract_amendments.confirm.approve_title'),
            $pending?->isDraft() ?? true => __('hr_contract_amendments.confirm.discard_title'),
            default => __('hr_contract_amendments.confirm.cancel_title'),
        }"
        :message="$pending === null ? null : match (true) {
            $pendingAction === 'approve' && $pending->effective_from->lte(today()) => __('hr_contract_amendments.confirm.approve_now', ['date' => $pending->effective_from->translatedFormat(__('formats.date'))]),
            $pendingAction === 'approve' => __('hr_contract_amendments.confirm.approve_later', ['date' => $pending->effective_from->translatedFormat(__('formats.date'))]),
            $pending->isDraft() => __('hr_contract_amendments.confirm.discard_body'),
            default => __('hr_contract_amendments.confirm.cancel_body', ['date' => $pending->effective_from->translatedFormat(__('formats.date'))]),
        }"
        confirm="confirmPending"
        :confirm-label="match (true) {
            $pendingAction === 'approve' => __('hr_contract_amendments.actions.approve'),
            $pending?->isDraft() ?? true => __('hr_contract_amendments.actions.discard'),
            default => __('hr_contract_amendments.actions.cancel'),
        }"
        confirm-test="confirm-amendment"
    />
</section>
