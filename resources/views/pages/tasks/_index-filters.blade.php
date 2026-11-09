{{--
    What sits between the register bar and the task views: the scope tabs,
    then the list's group/sort controls and the "my tasks only" toggle. (The
    view switch, filters and Clear live in the x-desk.register frame.) Included by
    ⚡index.blade.php, whose top PHP block defines $filtersAreActive.
--}}
    {{-- Whose tasks: one click to the viewer's own work, or what waits for their review. --}}
    <nav class="-mb-1 flex gap-1 overflow-x-auto border-b border-secondary-soft dark:border-line" aria-label="{{ __('tasks.tabs_label') }}" data-test="task-scope-tabs">
        @foreach (['' => __('tasks.tab_all'), 'mine' => __('tasks.tab_mine'), 'assigned' => __('tasks.tab_assigned'), 'review' => __('tasks.tab_review')] as $scopeKey => $scopeLabel)
            <button
                type="button"
                wire:click="setScope('{{ $scopeKey }}')"
                aria-current="{{ $scope === $scopeKey ? 'page' : 'false' }}"
                data-test="task-scope-{{ $scopeKey === '' ? 'all' : $scopeKey }}"
                @class([
                    '-mb-px inline-flex shrink-0 items-center gap-1.5 border-b-2 px-3 py-2 text-sm font-semibold transition-colors focus-visible:outline-2 focus-visible:outline-secondary-medium',
                    'border-primary text-secondary-dark dark:border-brand-text dark:text-ink' => $scope === $scopeKey,
                    'border-transparent text-secondary-medium hover:text-secondary-dark dark:text-ink-muted dark:hover:text-ink' => $scope !== $scopeKey,
                ])
            >
                {{ $scopeLabel }}
                @if ($scopeKey === 'review' && $this->tasksAwaitingMyReview > 0)
                    <span class="rounded-full bg-primary px-1.5 text-[11px] font-bold text-white tabular-nums dark:bg-status-critical dark:text-surface-0" data-test="task-scope-review-count">{{ $this->tasksAwaitingMyReview }}</span>
                @endif
            </button>
        @endforeach
    </nav>

    @if (! auth()->user()->can('tasks.update') && ! auth()->user()->can('create', App\Models\Task::class))
        <p class="flex items-start gap-2 rounded-xl bg-secondary-soft/40 px-3 py-2 text-xs text-secondary-dark dark:bg-white/[0.04] dark:text-ink-muted" data-test="task-view-only-notice">
            <flux:icon.eye variant="micro" class="mt-0.5 size-3.5 shrink-0" aria-hidden="true" />
            {{ __('tasks.view_only_notice') }}
        </p>
    @endif

    <div class="dk-reg-sub">
        <span></span>

        <div class="flex min-w-0 flex-wrap items-center gap-2">
            @if ($view === 'list')
                <x-ui.header-filter-select :label="__('tasks.group_by')" wire:model.live="group" :value="$group" class="w-40!">
                    <option value="none">{{ __('tasks.no_grouping') }}</option>
                    <option value="status">{{ __('common.status') }}</option>
                    <option value="priority">{{ __('tasks.priority') }}</option>
                    <option value="assignee">{{ __('tasks.assignee') }}</option>
                    <option value="milestone">{{ __('tasks.milestone') }}</option>
                </x-ui.header-filter-select>
                <x-ui.header-filter-select :label="__('tasks.sort_by')" wire:model.live="sortField" :value="$sortField" class="w-40!">
                    <option value="due">{{ __('tasks.due_date_2') }}</option>
                    <option value="title">{{ __('common.name') }}</option>
                    <option value="status">{{ __('common.status') }}</option>
                    <option value="priority">{{ __('tasks.priority') }}</option>
                    <option value="assignee">{{ __('tasks.assignee') }}</option>
                    <option value="created">{{ __('tasks.created_date') }}</option>
                </x-ui.header-filter-select>
                <x-ui.button
                    size="sm"
                    wire:click="$set('sortDirection', '{{ $sortDirection === 'asc' ? 'desc' : 'asc' }}')"
                    :aria-label="$sortDirection === 'asc' ? __('tasks.sort_ascending') : __('tasks.sort_descending')"
                    data-test="task-sort-direction-toggle"
                >
                    <flux:icon :name="$sortDirection === 'asc' ? 'bars-arrow-up' : 'bars-arrow-down'" variant="micro" class="size-4" aria-hidden="true" />
                </x-ui.button>
            @endif

            @if ($view !== 'cards')
            <button
                type="button"
                wire:click="toggleMyTasksOnly"
                aria-pressed="{{ $myTasksOnly ? 'true' : 'false' }}"
                data-test="task-filter-my-tasks-only"
                @class([
                    'inline-flex shrink-0 items-center gap-1.5 rounded-md border px-2.5 py-1.5 text-xs font-semibold transition-colors focus-visible:outline-2 focus-visible:outline-secondary-medium',
                    'border-primary bg-primary dark:bg-ink dark:text-surface-0 dark:border-ink text-white' => $myTasksOnly,
                    'border-secondary-light text-secondary-medium hover:bg-secondary-soft dark:text-ink-muted dark:hover:bg-surface-2' => ! $myTasksOnly,
                ])
            >
                <flux:icon.user variant="micro" class="size-3.5" />
                {{ __('tasks.my_tasks_only') }}
            </button>
            @endif

            @if (! in_array($view, ['calendar', 'cards'], true))
                {{-- Opens or closes every task's subtasks at once; remembered per browser (task-subtasks.js). --}}
                <button
                    type="button"
                    x-data
                    x-on:click="$store.taskSubtasks.toggleAll()"
                    x-bind:aria-pressed="$store.taskSubtasks.allOpen ? 'true' : 'false'"
                    x-bind:class="$store.taskSubtasks.allOpen ? 'border-primary bg-primary text-white dark:border-ink dark:bg-ink dark:text-surface-0' : 'border-secondary-light text-secondary-medium hover:bg-secondary-soft dark:text-ink-muted dark:hover:bg-surface-2'"
                    class="inline-flex shrink-0 items-center gap-1.5 rounded-md border px-2.5 py-1.5 text-xs font-semibold transition-colors focus-visible:outline-2 focus-visible:outline-secondary-medium"
                    data-test="task-subtasks-toggle-all"
                >
                    <flux:icon.rectangle-stack variant="micro" class="size-3.5" />
                    <span x-text="$store.taskSubtasks.allOpen ? {{ Illuminate\Support\Js::from(__('tasks.hide_subtasks')) }} : {{ Illuminate\Support\Js::from(__('tasks.show_subtasks')) }}">{{ __('tasks.show_subtasks') }}</span>
                </button>
            @endif
        </div>
    </div>
