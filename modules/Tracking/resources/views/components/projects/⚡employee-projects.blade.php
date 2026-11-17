<?php

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Modules\Tracking\Models\ProjectMember;
use Modules\Tracking\Support\EmployeeProjectsTab;

/**
 * An employee's projects (roadmap 2.7, D4): the ones they are on now and the
 * ones they were on before, with role and dates, read from project_members.
 * Drawn as the "Projects" tab of the HR profile through the module registry.
 */
new class extends Component {
    #[Locked]
    public int $employeeId = 0;

    public function mount(int $employeeId): void
    {
        $this->employeeId = $employeeId;

        abort_unless(Auth::user() !== null && app(EmployeeProjectsTab::class)->visibleTo(Auth::user(), $employeeId), 403);
    }

    /** @return Collection<int, ProjectMember> */
    #[Computed]
    public function memberships(): Collection
    {
        return ProjectMember::query()
            ->where('employee_id', $this->employeeId)
            ->with('project:id,name,slug,status')
            ->orderByRaw('ends_on IS NULL DESC')
            ->orderByDesc('ends_on')
            ->orderByDesc('starts_on')
            ->orderByDesc('id')
            ->get();
    }
};
?>

@php
    $memberships = $this->memberships;
    $current = $memberships->filter(fn ($membership) => $membership->isCurrent());
    $past = $memberships->reject(fn ($membership) => $membership->isCurrent());
    $muted = 'text-secondary-medium dark:text-ink-muted';
    $date = fn ($value) => $value?->translatedFormat(__('formats.date'));
@endphp

<div data-test="employee-projects">
    <x-ui.panel :title="__('projects.employee_projects.title')" :subtitle="__('projects.employee_projects.subtitle')" :count="$current->count()">
        <div class="flex flex-col gap-4 p-4">
            @foreach ([['current', $current, __('projects.employee_projects.current')], ['past', $past, __('projects.employee_projects.past')]] as [$group, $rows, $heading])
                @if ($group === 'current' || $rows->isNotEmpty())
                    <div>
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wide {{ $muted }}">{{ $heading }}</p>

                        @if ($rows->isEmpty())
                            <p class="text-sm {{ $muted }}" data-test="employee-projects-empty">{{ __('projects.employee_projects.empty') }}</p>
                        @else
                            <ul class="divide-y divide-panel-border" data-test="employee-projects-{{ $group }}">
                                @foreach ($rows as $membership)
                                    <li class="flex flex-wrap items-center justify-between gap-2 py-2" wire:key="employee-project-{{ $membership->id }}" data-membership="{{ $membership->id }}">
                                        <a href="{{ route('projects.show', $membership->project) }}" wire:navigate class="text-sm font-semibold text-secondary-dark hover:underline dark:text-ink">{{ $membership->project->name }}</a>

                                        <div class="flex flex-wrap items-center gap-2 text-xs {{ $muted }}">
                                            <span class="rounded-md bg-secondary-soft px-1.5 py-0.5 font-semibold text-secondary-dark dark:bg-surface-2 dark:text-ink">{{ $membership->role->label() }}</span>
                                            <span>
                                                @if ($membership->isCurrent())
                                                    {{ $membership->starts_on === null ? __('projects.team.start_unknown') : __('projects.team.since', ['date' => $date($membership->starts_on)]) }}
                                                @else
                                                    {{ __('projects.team.period', ['from' => $date($membership->starts_on) ?? __('projects.team.start_unknown'), 'to' => $date($membership->ends_on)]) }}
                                                @endif
                                            </span>
                                            @if ($membership->source === \Modules\Tracking\Enums\ProjectMemberSource::Inferred)
                                                <span class="italic">{{ __('projects.team.inferred') }}</span>
                                            @endif
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endif
            @endforeach
        </div>
    </x-ui.panel>
</div>
