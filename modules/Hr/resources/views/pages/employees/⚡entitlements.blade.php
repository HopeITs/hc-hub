<?php

use Carbon\CarbonImmutable;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Modules\Core\Models\User;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\EmployeeEntitlement;
use Modules\Hr\Models\Entitlement;
use Modules\Hr\Support\Entitlements\Entitlements;

/*
 * The Professional access tab of an employee profile (meeting 19/11, track
 * 5): the Permissions Library items this person should have outside the
 * system, what was done and what was taken back. Mounted by ⚡show only for
 * HR of their city, the administrators and their direct manager
 * (Entitlements::canAssign); for anyone else it renders nothing. Every action
 * re-checks that on the server through the service.
 */
new class extends Component {
    #[Locked]
    public int $employeeId;

    public string $addId = '';

    public string $note = '';

    public function mount(int $employeeId): void
    {
        $this->employeeId = $employeeId;
    }

    #[Computed]
    public function employee(): Employee
    {
        return Employee::query()->findOrFail($this->employeeId);
    }

    #[Computed]
    public function allowed(): bool
    {
        return app(Entitlements::class)->canAssign(Auth::user(), $this->employee);
    }

    /** @return Collection<int, EmployeeEntitlement> */
    #[Computed]
    public function rows(): Collection
    {
        return $this->allowed ? app(Entitlements::class)->forEmployee($this->employee) : collect();
    }

    /** @return EloquentCollection<int, Entitlement> */
    #[Computed]
    public function available(): EloquentCollection
    {
        if (! $this->allowed) {
            return new EloquentCollection;
        }

        $openIds = $this->rows->where('state', '!=', EmployeeEntitlement::STATE_REVOKED)->pluck('entitlement_id')->all();

        return Entitlement::query()->active()->whereNotIn('id', $openIds)->orderBy(Entitlement::translatedColumn('name'))->get();
    }

    public function add(Entitlements $entitlements): void
    {
        $employee = $this->authorizedEmployee($entitlements);

        $this->validate([
            'addId' => ['required', 'integer'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $entitlement = Entitlement::query()->active()->findOrFail((int) $this->addId);
        $added = $entitlements->add($employee, $entitlement, $this->actor(), trim($this->note) ?: null);

        $this->reset('addId', 'note');
        $this->flush();
        Flux::toast(variant: $added ? 'success' : 'warning', text: $added ? __('hr_entitlements.added') : __('hr_entitlements.already'));
    }

    public function markGranted(int $rowId, Entitlements $entitlements): void
    {
        $changed = $entitlements->markGranted($this->row($rowId, $entitlements), $this->actor());

        $this->flush();
        Flux::toast(variant: $changed ? 'success' : 'warning', text: $changed ? __('hr_entitlements.granted_done') : __('hr_entitlements.nothing_changed'));
    }

    public function revoke(int $rowId, Entitlements $entitlements): void
    {
        $changed = $entitlements->revoke($this->row($rowId, $entitlements), $this->actor());

        $this->flush();
        Flux::toast(variant: $changed ? 'success' : 'warning', text: $changed ? __('hr_entitlements.revoked_done') : __('hr_entitlements.nothing_changed'));
    }

    public function keep(int $rowId, Entitlements $entitlements): void
    {
        $changed = $entitlements->keep($this->row($rowId, $entitlements), $this->actor());

        $this->flush();
        Flux::toast(variant: $changed ? 'success' : 'warning', text: $changed ? __('hr_entitlements.kept') : __('hr_entitlements.nothing_changed'));
    }

    /** Re-checked on every action: a role switch or a team change since mount counts. */
    private function authorizedEmployee(Entitlements $entitlements): Employee
    {
        $employee = Employee::query()->findOrFail($this->employeeId);
        abort_unless($entitlements->canAssign(Auth::user(), $employee), 403);

        return $employee;
    }

    /** Only a row of this panel's person, never an id from another one. */
    private function row(int $rowId, Entitlements $entitlements): EmployeeEntitlement
    {
        $employee = $this->authorizedEmployee($entitlements);

        return EmployeeEntitlement::query()->where('employee_id', $employee->id)->findOrFail($rowId);
    }

    private function actor(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }

    private function flush(): void
    {
        unset($this->rows, $this->available);
    }
};
?>

@php
    $muted = 'text-secondary-medium dark:text-ink-muted';
    $date = fn (mixed $value): string => $value === null ? '—' : CarbonImmutable::parse($value)->timezone(config('app.timezone'))->translatedFormat(__('formats.date'));
    $by = fn (?User $user): string => $user?->name ?: __('hr_journeys.unknown');
    $tones = [
        EmployeeEntitlement::STATE_REQUIRED => 'bg-status-warn-soft text-status-warn',
        EmployeeEntitlement::STATE_GRANTED => 'bg-status-good-soft text-status-good',
        EmployeeEntitlement::STATE_REVOKED => 'bg-secondary-soft text-secondary-dark dark:bg-surface-2 dark:text-ink',
    ];
@endphp

<div>
    @if ($this->allowed)
        <x-ui.panel :title="__('hr_entitlements.panel_title')" :subtitle="__('hr_entitlements.panel_lead')" :count="$this->rows->count()" data-entitlements-panel>
            @if ($this->rows->isEmpty())
                <p class="p-4 text-sm {{ $muted }}">{{ __('hr_entitlements.panel_empty') }}</p>
            @else
                <ul class="divide-y divide-panel-border">
                    @foreach ($this->rows as $row)
                        <li wire:key="ent-row-{{ $row->id }}" data-state="{{ $row->state }}" class="grid gap-2 px-4 py-3 sm:grid-cols-[minmax(0,1fr)_auto]">
                            <div class="min-w-0">
                                <p class="flex flex-wrap items-center gap-2 font-semibold">
                                    <span dir="auto">{{ $row->entitlement?->name }}</span>
                                    <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold whitespace-nowrap {{ $tones[$row->state] }}">{{ __('hr_entitlements.state.'.$row->state) }}</span>
                                    <span class="inline-flex items-center rounded-md bg-secondary-soft px-2 py-0.5 text-xs font-semibold text-secondary-dark dark:bg-surface-2 dark:text-ink">{{ __('hr_entitlements.categories.'.$row->entitlement?->category) }}</span>
                                    @if ($row->source === EmployeeEntitlement::SOURCE_MANUAL)
                                        <span class="inline-flex items-center rounded-md bg-secondary-soft px-2 py-0.5 text-xs font-semibold text-secondary-dark dark:bg-surface-2 dark:text-ink">{{ __('hr_entitlements.source.manual') }}</span>
                                    @endif
                                    @if ($row->needs_review_at)
                                        <span class="inline-flex items-center rounded-md bg-status-warn-soft px-2 py-0.5 text-xs font-semibold text-status-warn">{{ __('hr_entitlements.needs_review') }}</span>
                                    @endif
                                </p>
                                <p class="mt-1 text-xs {{ $muted }}">
                                    @if ($row->state === EmployeeEntitlement::STATE_GRANTED)
                                        {{ __('hr_entitlements.granted_by', ['date' => $date($row->granted_at), 'name' => $by($row->grantedBy)]) }}
                                    @elseif ($row->state === EmployeeEntitlement::STATE_REVOKED)
                                        {{ __('hr_entitlements.revoked_by', ['date' => $date($row->revoked_at), 'name' => $by($row->revokedBy)]) }}
                                    @endif
                                    @if ($row->needs_review_at)
                                        {{ __('hr_entitlements.needs_review_help') }}
                                    @endif
                                    @if ($row->entitlement?->mailList)
                                        <span dir="ltr">{{ $row->entitlement->mailList->address }}</span>
                                    @elseif ($row->entitlement?->reference)
                                        <span dir="ltr">{{ $row->entitlement->reference }}</span>
                                    @endif
                                </p>
                                @if ($row->note)
                                    <p class="mt-1 text-xs {{ $muted }}">{{ $row->note }}</p>
                                @endif
                            </div>
                            <div class="flex flex-wrap items-start gap-2 sm:justify-end">
                                @if ($row->state === EmployeeEntitlement::STATE_REQUIRED)
                                    <x-ui.button size="sm" icon="check" wire:click="markGranted({{ $row->id }})" data-test="ent-grant-{{ $row->id }}">{{ __('hr_entitlements.mark_granted') }}</x-ui.button>
                                @endif
                                @if ($row->needs_review_at)
                                    <x-ui.button tone="ghost" size="sm" wire:click="keep({{ $row->id }})">{{ __('hr_entitlements.keep') }}</x-ui.button>
                                @endif
                                @if ($row->state !== EmployeeEntitlement::STATE_REVOKED)
                                    <x-ui.button tone="ghost" size="sm" wire:click="revoke({{ $row->id }})" wire:confirm="{{ __('hr_entitlements.revoke_confirm') }}">{{ __('hr_entitlements.revoke') }}</x-ui.button>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if ($this->available->isNotEmpty())
                <form wire:submit="add" class="grid gap-3 border-t border-panel-border p-4 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] sm:items-end">
                    <x-ui.form-select wire:model="addId" :label="__('hr_entitlements.add_item')">
                        <option value="">—</option>
                        @foreach ($this->available as $entitlement)
                            <option value="{{ $entitlement->id }}">{{ $entitlement->name }}</option>
                        @endforeach
                    </x-ui.form-select>
                    <x-ui.form-input wire:model="note" :label="__('hr_entitlements.note')" maxlength="500" />
                    <x-ui.button type="submit" data-test="ent-add-submit">{{ __('hr_entitlements.add') }}</x-ui.button>
                </form>
            @endif
        </x-ui.panel>
    @endif
</div>
