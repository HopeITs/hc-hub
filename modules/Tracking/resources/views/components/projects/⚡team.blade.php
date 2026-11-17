<?php

use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Modules\Hr\Models\Employee;
use Modules\Tracking\Actions\ProjectMembers\AddProjectMember;
use Modules\Tracking\Actions\ProjectMembers\EndProjectMember;
use Modules\Tracking\Enums\ProjectMemberRole;
use Modules\Tracking\Models\Project;
use Modules\Tracking\Models\ProjectMember;

/**
 * A project's team (roadmap 2.4, D4): who is on it now and who was before,
 * with their role and dates. The project's manager adds members and ends
 * memberships; everyone else who can see the project only reads it. Ending
 * keeps the row, so earlier members stay listed with their end date.
 */
new class extends Component {
    #[Locked]
    public int $projectId = 0;

    public bool $showAddModal = false;

    /** @var array{employee_id: string, role: string, starts_on: string} */
    public array $memberForm = ['employee_id' => '', 'role' => 'member', 'starts_on' => ''];

    public ?int $endingMemberId = null;

    public bool $showEndModal = false;

    public string $endsOn = '';

    public function mount(int $projectId): void
    {
        $this->projectId = $projectId;

        $this->authorize('viewAny', [ProjectMember::class, $this->project()]);
    }

    public function project(): Project
    {
        return Project::query()->findOrFail($this->projectId);
    }

    #[Computed]
    public function canManage(): bool
    {
        return Gate::allows('manage', [ProjectMember::class, $this->project()]);
    }

    /** @return Collection<int, ProjectMember> */
    #[Computed]
    public function members(): Collection
    {
        return ProjectMember::query()
            ->where('project_id', $this->projectId)
            ->with('employee:id,first_name_en,last_name_en,first_name_ar,last_name_ar')
            ->orderByRaw("FIELD(role, 'owner') DESC")
            ->orderBy('starts_on')
            ->orderBy('id')
            ->get();
    }

    /** @return Collection<int, Employee> */
    #[Computed]
    public function employeeOptions(): Collection
    {
        $active = array_flip($this->activeEmployeeIds());

        return Employee::pickerOptions()->filter(fn (Employee $employee): bool => isset($active[(int) $employee->id]))->values();
    }

    /** @return list<int> */
    private function activeEmployeeIds(): array
    {
        return Employee::query()->where('is_active', true)->pluck('id')->map(fn (mixed $id): int => (int) $id)->all();
    }

    public function openAddModal(): void
    {
        $this->authorize('manage', [ProjectMember::class, $this->project()]);

        $this->resetValidation();
        $this->memberForm = ['employee_id' => '', 'role' => ProjectMemberRole::Member->value, 'starts_on' => ''];
        $this->showAddModal = true;
    }

    public function addMember(): void
    {
        try {
            app(AddProjectMember::class)->handle(
                $this->project(),
                (int) $this->memberForm['employee_id'],
                $this->memberForm['role'],
                $this->memberForm['starts_on'] === '' ? null : $this->memberForm['starts_on'],
            );
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages(collect($exception->errors())->mapWithKeys(fn (array $messages, string $key): array => ['memberForm.'.$key => $messages])->all());
        }

        $this->showAddModal = false;
        unset($this->members);

        Flux::toast(variant: 'success', text: __('projects.team.member_added'));
    }

    public function askEnd(int $memberId): void
    {
        $this->authorize('manage', [ProjectMember::class, $this->project()]);

        $this->resetValidation();
        $this->endingMemberId = $memberId;
        $this->endsOn = now()->toDateString();
        $this->showEndModal = true;
    }

    public function cancelEnd(): void
    {
        $this->showEndModal = false;
        $this->endingMemberId = null;
    }

    public function endMember(): void
    {
        $member = ProjectMember::query()->where('project_id', $this->projectId)->findOrFail((int) $this->endingMemberId);

        try {
            $ended = app(EndProjectMember::class)->handle($member, $this->endsOn === '' ? null : $this->endsOn);
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages(['endsOn' => collect($exception->errors())->flatten()->all()]);
        }

        $this->showEndModal = false;
        $this->endingMemberId = null;
        unset($this->members);

        Flux::toast(variant: $ended ? 'success' : 'warning', text: $ended ? __('projects.team.member_ended') : __('projects.team.already_ended'));
    }
};
?>

@php
    $members = $this->members;
    $current = $members->filter(fn ($member) => $member->isCurrent());
    $past = $members->reject(fn ($member) => $member->isCurrent());
    $canManage = $this->canManage;
    $ending = $endingMemberId === null ? null : $members->firstWhere('id', $endingMemberId);
@endphp

<div data-test="project-team">
    <x-ui.panel :title="__('projects.team.title')" :subtitle="__('projects.team.subtitle')" :count="$current->count()">
        @if ($canManage)
            <x-slot:toolbar>
                <x-ui.button tone="primary" size="sm" icon="user-plus" wire:click="openAddModal" data-test="team-add-button">{{ __('projects.team.add') }}</x-ui.button>
            </x-slot:toolbar>
        @endif

        <div class="flex flex-col gap-4 p-4">
            <div>
                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-secondary-medium dark:text-ink-muted">{{ __('projects.team.current') }}</p>
                @if ($current->isEmpty())
                    <p class="text-sm text-secondary-medium dark:text-ink-muted" data-test="team-empty">{{ __('projects.team.empty') }}</p>
                @else
                    <ul class="divide-y divide-panel-border" data-test="team-current">
                        @foreach ($current as $member)
                            <li class="flex flex-wrap items-center justify-between gap-2 py-2" wire:key="team-member-{{ $member->id }}" data-member="{{ $member->id }}">
                                <x-ui.employee-chip :employee="$member->employee" compact />
                                <div class="flex flex-wrap items-center gap-2 text-xs text-secondary-medium dark:text-ink-muted">
                                    <span class="rounded-md bg-secondary-soft px-1.5 py-0.5 font-semibold text-secondary-dark dark:bg-surface-2 dark:text-ink">{{ $member->role->label() }}</span>
                                    <span>{{ $member->starts_on === null ? __('projects.team.start_unknown') : __('projects.team.since', ['date' => $member->starts_on->translatedFormat(__('formats.date'))]) }}</span>
                                    @if ($member->source === \Modules\Tracking\Enums\ProjectMemberSource::Inferred)
                                        <span class="italic">{{ __('projects.team.inferred') }}</span>
                                    @endif
                                    @if ($canManage && $member->role !== \Modules\Tracking\Enums\ProjectMemberRole::Owner)
                                        <x-ui.button tone="ghost" size="sm" wire:click="askEnd({{ $member->id }})" data-test="team-end-{{ $member->id }}">{{ __('projects.team.end') }}</x-ui.button>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            @if ($past->isNotEmpty())
                <div>
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-secondary-medium dark:text-ink-muted">{{ __('projects.team.past') }}</p>
                    <ul class="divide-y divide-panel-border" data-test="team-past">
                        @foreach ($past as $member)
                            <li class="flex flex-wrap items-center justify-between gap-2 py-2" wire:key="team-past-{{ $member->id }}" data-member="{{ $member->id }}">
                                <x-ui.employee-chip :employee="$member->employee" compact />
                                <div class="flex flex-wrap items-center gap-2 text-xs text-secondary-medium dark:text-ink-muted">
                                    <span class="rounded-md bg-secondary-soft px-1.5 py-0.5 font-semibold text-secondary-dark dark:bg-surface-2 dark:text-ink">{{ $member->role->label() }}</span>
                                    <span>{{ __('projects.team.period', [
                                        'from' => $member->starts_on?->translatedFormat(__('formats.date')) ?? __('projects.team.start_unknown'),
                                        'to' => $member->ends_on->translatedFormat(__('formats.date')),
                                    ]) }}</span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @unless ($canManage)
                <p class="text-xs text-secondary-medium dark:text-ink-muted" data-test="team-read-only">{{ __('projects.team.read_only') }}</p>
            @endunless
        </div>
    </x-ui.panel>

    @if ($canManage)
        <x-ui.modal wire:model="showAddModal" size="md" :title="__('projects.team.add_title')" submit="addMember">
            @if ($showAddModal)
                <div class="flex flex-col gap-4">
                    <x-ui.form-select wire:model="memberForm.employee_id" :label="__('projects.team.employee')" :placeholder="__('common.select')" required data-test="team-employee-select">
                        @foreach ($this->employeeOptions as $option)
                            <option value="{{ $option->id }}">{{ $option->name }} · {{ $option->secondary_name }}</option>
                        @endforeach
                    </x-ui.form-select>
                    <x-ui.form-select wire:model="memberForm.role" :label="__('projects.team.role')" required data-test="team-role-select">
                        @foreach (\Modules\Tracking\Enums\ProjectMemberRole::assignable() as $role)
                            <option value="{{ $role->value }}">{{ $role->label() }}</option>
                        @endforeach
                    </x-ui.form-select>
                    <x-ui.form-input type="date" wire:model="memberForm.starts_on" :label="__('projects.team.starts_on')" :help="__('projects.team.starts_on_help')" max="{{ now()->toDateString() }}" data-test="team-starts-on" />
                </div>
            @endif

            <x-slot:actions>
                <x-ui.button type="submit" tone="primary" data-test="team-add-submit">{{ __('projects.team.add') }}</x-ui.button>
            </x-slot:actions>
        </x-ui.modal>

        <x-ui.confirm-modal
            wire:model="showEndModal"
            close="cancelEnd"
            tone="neutral"
            :title="__('projects.team.end_title')"
            :message="$ending !== null ? __('projects.team.end_body', ['name' => $ending->employee?->name]) : null"
            confirm="endMember"
            :confirm-label="__('projects.team.end')"
            confirm-test="team-end-confirm"
        >
            @if ($ending !== null)
                <x-ui.form-input type="date" wire:model="endsOn" :label="__('projects.team.ends_on')" max="{{ now()->toDateString() }}" data-test="team-ends-on" />
            @endif
        </x-ui.confirm-modal>
    @endif
</div>
