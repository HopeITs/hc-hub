<?php

use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Modules\Core\Models\City;
use Modules\Core\Models\Program;
use Modules\Core\Models\User;
use Modules\Hr\Models\Department;
use Modules\Hr\Models\MailList;
use Modules\Hr\Models\MailListMember;
use Modules\Hr\Support\MailLists\MailListSync;

/*
 * Each mailing list against the people who should be on it by HR data: who is
 * missing, who is on it and does not match its rule, and who has left the
 * organisation. HR (mail-lists.manage, checked by the route) edits the rule of
 * a list and queues the missing people as pending for the person who holds
 * the control panel; nothing is changed at the mail provider from here.
 */
new #[Layout('layouts::desk'), Title('hr_mail_lists.check.title')] class extends Component {
    public bool $showRuleModal = false;

    #[Locked]
    public ?int $editId = null;

    public string $ruleType = '';

    public string $ruleCity = '';

    public string $ruleProgram = '';

    public string $ruleDepartment = '';

    public bool $ruleManagersOnly = false;

    public function mount(): void
    {
        $this->authorizeManage();
    }

    public function editRule(int $listId): void
    {
        $this->authorizeManage();

        $list = MailList::query()->findOrFail($listId);

        $this->resetValidation();
        $this->editId = $list->id;
        $this->ruleType = (string) $list->rule_type;
        $this->ruleCity = (string) ($list->rule_city_id ?? '');
        $this->ruleProgram = (string) ($list->rule_program_id ?? '');
        $this->ruleDepartment = (string) ($list->rule_department_id ?? '');
        $this->ruleManagersOnly = $list->rule_managers_only;
        $this->showRuleModal = true;
    }

    public function closeRule(): void
    {
        $this->showRuleModal = false;
        $this->editId = null;
        $this->resetValidation();
    }

    public function saveRule(): void
    {
        $this->authorizeManage();

        $this->validate([
            'ruleType' => ['nullable', Rule::in(MailList::RULES)],
            'ruleCity' => ['nullable', 'integer', Rule::exists('cities', 'id')],
            'ruleProgram' => ['nullable', 'integer', Rule::exists('programs', 'id')],
            'ruleDepartment' => ['nullable', 'integer', Rule::exists('departments', 'id')],
        ]);

        $custom = $this->ruleType === MailList::RULE_CUSTOM;

        // A custom rule with no filter at all would be everyone: that is the all-staff rule.
        if ($custom && $this->ruleCity === '' && $this->ruleProgram === '' && $this->ruleDepartment === '' && ! $this->ruleManagersOnly) {
            $this->addError('ruleType', __('hr_mail_lists.check.rule_empty'));

            return;
        }

        MailList::query()->findOrFail((int) $this->editId)->update([
            'rule_type' => $this->ruleType === '' ? null : $this->ruleType,
            'rule_city_id' => $custom && $this->ruleCity !== '' ? (int) $this->ruleCity : null,
            'rule_program_id' => $custom && $this->ruleProgram !== '' ? (int) $this->ruleProgram : null,
            'rule_department_id' => $custom && $this->ruleDepartment !== '' ? (int) $this->ruleDepartment : null,
            'rule_managers_only' => $custom && $this->ruleManagersOnly,
        ]);

        $this->closeRule();
        Flux::toast(variant: 'success', text: __('hr_mail_lists.check.rule_saved'));
    }

    /** Queue everyone missing from one list as pending. */
    public function queueMissing(int $listId): void
    {
        $this->authorizeManage();

        $count = app(MailListSync::class)->queueMissing(MailList::query()->findOrFail($listId), $this->user()->id);

        Flux::toast(variant: 'success', text: trans_choice('hr_mail_lists.check.queued', $count, ['count' => $count]));
    }

    public function queueOne(int $listId, string $email): void
    {
        $this->authorizeManage();

        $done = app(MailListSync::class)->queueOne(MailList::query()->findOrFail($listId), $email, $this->user()->id);

        Flux::toast(variant: $done ? 'success' : 'warning', text: $done ? trans_choice('hr_mail_lists.check.queued', 1, ['count' => 1]) : __('hr_mail_lists.check.not_expected'));
    }

    /** Take a person off the list here (they still have to be removed at the control panel). */
    public function removeMember(int $memberId): void
    {
        $this->authorizeManage();

        MailListMember::query()->findOrFail($memberId)->delete();
        Flux::toast(variant: 'success', text: __('hr_mail_lists.removed'));
    }

    /** Someone who left: flag them for removal at the control panel (they stay on the list until it is done). */
    public function queueRemoval(int $memberId): void
    {
        $this->authorizeManage();

        app(MailListSync::class)->queueRemoval(MailListMember::query()->findOrFail($memberId));
        Flux::toast(variant: 'success', text: __('hr_mail_lists.check.removal_queued'));
    }

    /** @return array<string, mixed> */
    public function with(): array
    {
        $sync = app(MailListSync::class);

        $rows = MailList::query()->orderBy('position')->orderBy('name')->get()
            ->map(fn (MailList $list): array => [
                'list' => $list,
                'result' => $list->hasRule() ? $sync->compare($list) : null,
            ]);

        return [
            'rows' => $rows,
            'checked' => $rows->filter(fn (array $row): bool => $row['result'] !== null)->count(),
            'cities' => City::query()->active()->orderBy(City::nameColumn())->get(),
            'programs' => Program::namesById(),
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
        ];
    }

    private function authorizeManage(): void
    {
        abort_unless($this->user()->can('mail-lists.manage'), 403);
    }

    private function user(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
};
?>

@use('App\Support\BackUrl')

@php
    $muted = 'text-secondary-medium dark:text-ink-muted';
    $columns = [
        'missing' => ['help' => null, 'icon' => 'plus'],
        'not_matching' => ['help' => 'not_matching_help', 'icon' => 'trash'],
        'left' => ['help' => 'left_help', 'icon' => 'trash'],
    ];
@endphp

<section class="@container flex flex-col gap-5" data-hr-mail-lists-check>
    <x-ui.detail-header
        :back="BackUrl::for(route('hr.mail-lists.index'))"
        :breadcrumbs="[
            ['label' => __('hr_mail_lists.title'), 'url' => route('hr.mail-lists.index')],
            ['label' => __('hr_mail_lists.check.title')],
        ]"
        :title="__('hr_mail_lists.check.title')"
    />

    <p class="text-sm {{ $muted }}">{{ __('hr_mail_lists.check.lead') }}</p>

    @if ($checked === 0)
        <x-ui.empty-state icon="envelope" :heading="__('hr_mail_lists.check.none')" :body="__('hr_mail_lists.check.none_help')" />
    @endif

    <div class="flex flex-col gap-4" data-test="mail-list-check">
        @foreach ($rows as $row)
            @php
                $list = $row['list'];
                $result = $row['result'];
                $ruleText = match ($list->rule_type) {
                    MailList::RULE_ALL_STAFF => __('hr_mail_lists.check.rule_all_staff'),
                    MailList::RULE_BOARD => __('hr_mail_lists.check.rule_board'),
                    MailList::RULE_CUSTOM => __('hr_mail_lists.check.rule_custom').': '.collect([
                        $list->rule_city_id ? $cities->firstWhere('id', $list->rule_city_id)?->name : null,
                        $list->rule_program_id ? ($programs[$list->rule_program_id] ?? null) : null,
                        $list->rule_department_id ? $departments->firstWhere('id', $list->rule_department_id)?->name : null,
                        $list->rule_managers_only ? __('hr_mail_lists.check.managers_only') : null,
                    ])->filter()->implode(' · '),
                    default => __('hr_mail_lists.check.no_rule'),
                };
            @endphp
            <x-ui.panel class="p-4" wire:key="check-{{ $list->id }}" data-mail-list-check="{{ $list->address }}">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h2 class="truncate font-semibold" dir="auto">{{ $list->name }}</h2>
                        <span class="block truncate text-sm {{ $muted }}" dir="ltr">{{ $list->address }}</span>
                        <span class="mt-1 block text-xs {{ $muted }}">{{ $ruleText }}</span>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        @if ($result)
                            @if ($result['in_sync'])
                                <x-ui.tone-badge tone="good" icon="check">{{ __('hr_mail_lists.check.in_sync') }}</x-ui.tone-badge>
                            @else
                                <x-ui.tone-badge tone="warn" icon="exclamation-triangle">{{ __('hr_mail_lists.check.differs') }}</x-ui.tone-badge>
                            @endif
                        @endif
                        <x-ui.button tone="ghost" size="sm" icon="pencil-square" wire:click="editRule({{ $list->id }})" data-test="mail-list-edit-rule">{{ __('hr_mail_lists.check.edit_rule') }}</x-ui.button>
                    </div>
                </div>

                @if ($result && ! $result['in_sync'])
                    <div class="mt-4 grid gap-6 @5xl:grid-cols-3">
                        @foreach ($columns as $key => $column)
                            <div x-data="{ all: false }" data-test="check-{{ $key }}">
                                <div class="flex items-center justify-between gap-2">
                                    <h3 class="font-semibold">{{ __('hr_mail_lists.check.'.$key) }} <span class="tabular-nums {{ $muted }}">{{ $result[$key]->count() }}</span></h3>
                                    @if ($key === 'missing' && $result['missing']->isNotEmpty())
                                        <x-ui.button size="sm" icon="plus" wire:click="queueMissing({{ $list->id }})" data-test="mail-list-queue-all">{{ __('hr_mail_lists.check.queue_all') }}</x-ui.button>
                                    @endif
                                </div>
                                @if ($column['help'])
                                    <p class="text-xs {{ $muted }}">{{ __('hr_mail_lists.check.'.$column['help']) }}</p>
                                @endif
                                <ul class="mt-2 divide-y divide-panel-border">
                                    @foreach ($result[$key] as $index => $person)
                                        <li class="flex items-center justify-between gap-2 py-2" x-show="all || {{ $index }} < 8" wire:key="{{ $key }}-{{ $list->id }}-{{ $person['email'] }}">
                                            <span class="min-w-0">
                                                <span class="block truncate font-semibold" dir="auto">{{ $person['name'] }}</span>
                                                <span class="block truncate text-xs {{ $muted }}" dir="ltr">{{ $person['email'] }}</span>
                                            </span>
                                            @if ($key === 'missing')
                                                <x-ui.button tone="ghost" size="sm" icon="plus" wire:click="queueOne({{ $list->id }}, {{ \Illuminate\Support\Js::from($person['email']) }})" :aria-label="__('hr_mail_lists.check.queue_one')" data-test="mail-list-queue-one" />
                                            @else
                                                <span class="flex shrink-0 items-center gap-1">
                                                    @if ($key === 'left' && ! $person['leaving'])
                                                        <x-ui.button tone="ghost" size="sm" icon="user-minus" wire:click="queueRemoval({{ $person['id'] }})" :aria-label="__('hr_mail_lists.check.queue_removal')" data-test="mail-list-queue-removal" />
                                                    @elseif ($person['leaving'])
                                                        <x-ui.tone-badge tone="neutral">{{ __('hr_mail_lists.leaving_tag') }}</x-ui.tone-badge>
                                                    @endif
                                                    <x-ui.button tone="ghost" size="sm" icon="trash" wire:click="removeMember({{ $person['id'] }})" wire:confirm="{{ __('hr_mail_lists.remove_confirm') }}" :aria-label="__('hr_mail_lists.remove')" />
                                                </span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                                @if ($result[$key]->count() > 8)
                                    <button type="button" x-on:click="all = ! all" class="mt-1 text-xs font-semibold underline {{ $muted }}" x-text="all ? {{ \Illuminate\Support\Js::from(__('hr_mail_lists.check.show_less')) }} : {{ \Illuminate\Support\Js::from(__('hr_mail_lists.check.show_all', ['count' => $result[$key]->count()])) }}"></button>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-ui.panel>
        @endforeach
    </div>

    <x-ui.modal wire:model="showRuleModal" size="sm" :title="__('hr_mail_lists.check.edit_rule')" submit="saveRule" close="closeRule">
        @if ($showRuleModal)
            <div class="grid gap-y-3" data-test="mail-list-rule-form">
                <x-ui.form-select wire:model.live="ruleType" :label="__('hr_mail_lists.check.rule')">
                    <option value="">{{ __('hr_mail_lists.check.no_rule') }}</option>
                    @foreach (MailList::RULES as $rule)
                        <option value="{{ $rule }}">{{ __('hr_mail_lists.check.rule_'.$rule) }}</option>
                    @endforeach
                </x-ui.form-select>
                @if ($ruleType === MailList::RULE_CUSTOM)
                    <x-ui.form-select wire:model="ruleCity" :label="__('hr_mail_lists.check.city')" :placeholder="__('common.select')">
                        @foreach ($cities as $city)
                            <option value="{{ $city->id }}">{{ $city->name }}</option>
                        @endforeach
                    </x-ui.form-select>
                    <x-ui.form-select wire:model="ruleProgram" :label="__('hr_mail_lists.check.program')" :placeholder="__('common.select')">
                        @foreach ($programs as $programId => $programName)
                            <option value="{{ $programId }}">{{ $programName }}</option>
                        @endforeach
                    </x-ui.form-select>
                    <x-ui.form-select wire:model="ruleDepartment" :label="__('hr_mail_lists.check.department')" :placeholder="__('common.select')">
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                        @endforeach
                    </x-ui.form-select>
                    <flux:checkbox wire:model="ruleManagersOnly" :label="__('hr_mail_lists.check.managers_only')" />
                @endif
            </div>
        @endif
    </x-ui.modal>
</section>
