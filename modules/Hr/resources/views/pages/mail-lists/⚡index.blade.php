<?php

use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Modules\Core\Models\User;
use Modules\Hr\Models\MailList;
use Modules\Hr\Models\MailListMember;

/*
 * The organisation's mailing lists. Every member of staff reads them and
 * emails one (a mailto: to the list's own address: nothing is sent from
 * here). Editing members is mail-lists.manage (HR); a member added here is
 * pending until the person who holds the mail provider's control panel adds
 * them there and confirms it. That queue and its button are
 * mail-lists.confirm, given directly to one person, never through a role.
 * Members who left the organisation, and addresses outside it, are shown
 * only to those who manage.
 */
new #[Layout('layouts::desk'), Title('hr_mail_lists.title')] class extends Component {
    #[Url(except: '')]
    public string $search = '';

    /** '' for all, or one of MailList::CATEGORIES / 'other'. */
    #[Url(except: '')]
    public string $category = '';

    public bool $showListModal = false;

    #[Locked]
    public ?int $openId = null;

    public string $newEmail = '';

    public function updatedCategory(): void
    {
        $this->category = in_array($this->category, [...MailList::CATEGORIES, 'other'], true) ? $this->category : '';
    }

    public function openList(int $listId): void
    {
        $this->openId = MailList::query()->whereKey($listId)->value('id');
        $this->newEmail = '';
        $this->resetValidation();
        $this->showListModal = $this->openId !== null;
    }

    public function closeList(): void
    {
        $this->showListModal = false;
        $this->openId = null;
        $this->newEmail = '';
        $this->resetValidation();
    }

    public function addMember(): void
    {
        $this->authorizeManage();

        $list = MailList::query()->findOrFail((int) $this->openId);
        $email = Str::lower(trim($this->newEmail));

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->addError('newEmail', __('hr_mail_lists.bad_email'));

            return;
        }

        // Added here, so not yet in the group at the control panel.
        $list->members()->firstOrCreate(
            ['email' => $email],
            ['status' => MailListMember::PENDING, 'added_by_user_id' => $this->user()->id],
        );

        $this->newEmail = '';
        $this->resetValidation();
        Flux::toast(variant: 'success', text: __('hr_mail_lists.added'));
    }

    public function removeMember(int $memberId): void
    {
        $this->authorizeManage();

        MailListMember::query()->findOrFail($memberId)->delete();
        Flux::toast(variant: 'success', text: __('hr_mail_lists.removed'));
    }

    public function deleteList(int $listId): void
    {
        $this->authorizeManage();

        MailList::query()->findOrFail($listId)->delete();
        $this->closeList();
        Flux::toast(variant: 'success', text: __('hr_mail_lists.deleted'));
    }

    /** "Added in the control panel": the pending member is now in the group. */
    public function confirmMember(int $memberId): void
    {
        $this->authorizeConfirmer();

        $this->markActive(MailListMember::query()->pending()->whereKey($memberId));
    }

    /** Every pending addition of one person, across lists, at once. */
    public function confirmEmail(string $email): void
    {
        $this->authorizeConfirmer();

        $this->markActive(MailListMember::query()->pending()->where('email', Str::lower($email)));
        Flux::toast(variant: 'success', text: __('hr_mail_lists.confirmed'));
    }

    /** @return array<string, mixed> */
    public function with(): array
    {
        $canManage = $this->user()->can('mail-lists.manage');
        $canConfirm = $this->canConfirm();
        $lists = MailList::query()->with('members')->orderBy('position')->orderBy('name')->get();

        $accounts = User::query()
            ->with('employee:id,first_name_en,first_name_ar,last_name_en,last_name_ar,is_active')
            ->whereIn('email', $lists->flatMap->members->pluck('email')->unique()->all())
            ->get()
            ->keyBy(fn (User $account): string => Str::lower($account->email));

        $rows = $lists->map(function (MailList $list) use ($accounts, $canManage): array {
            $members = $list->members->map(function (MailListMember $member) use ($accounts): array {
                $account = $accounts->get($member->email);
                $employee = $account?->employee;

                return [
                    'id' => $member->id,
                    'email' => $member->email,
                    'name' => $employee?->name,
                    'former' => $employee !== null && ! $employee->is_active,
                    'external' => $account === null && ! str_ends_with($member->email, '@'.MailList::DOMAIN),
                    'pending' => $member->isPending(),
                ];
            })
                ->filter(fn (array $member): bool => $canManage || (! $member['former'] && ! $member['external']))
                ->sortBy(fn (array $member): string => Str::lower($member['name'] ?? $member['email']))
                ->values();

            return ['list' => $list, 'members' => $members, 'category' => MailList::categoryOf($list->address), 'matches' => collect()];
        });

        $all = $rows;
        $people = $rows->flatMap(fn (array $row): Collection => $row['members']->pluck('email'))->unique()->count();

        // A list's name or address, or a person on it (shown as who matched).
        $term = Str::lower(trim($this->search));

        if ($term !== '') {
            $rows = $rows->map(function (array $row) use ($term): ?array {
                $byList = str_contains(Str::lower($row['list']->name.' '.$row['list']->address), $term);
                $matches = $row['members']->filter(fn (array $member): bool => str_contains(Str::lower(($member['name'] ?? '').' '.$member['email']), $term));

                return $byList || $matches->isNotEmpty() ? [...$row, 'matches' => $byList ? collect() : $matches->take(3)->values()] : null;
            })->filter()->values();
        }

        $categories = collect([...MailList::CATEGORIES, 'other'])
            ->mapWithKeys(fn (string $key): array => [$key => $rows->where('category', $key)->count()])
            ->filter();

        if ($this->category !== '') {
            $rows = $rows->where('category', $this->category)->values();
        }

        return [
            'rows' => $rows,
            'listCount' => $lists->count(),
            'people' => $people,
            'categories' => $categories,
            'open' => $this->openId === null ? null : $all->firstWhere('list.id', $this->openId),
            'canManage' => $canManage,
            'canConfirm' => $canConfirm,
            'pending' => $canConfirm ? $this->pendingQueue($accounts) : collect(),
        ];
    }

    /**
     * The confirmer's queue: one line per person, with the lists to add them to.
     *
     * @param  Collection<string, User>  $accounts
     * @return Collection<int, array{email: string, name: ?string, lists: list<string>, by: ?string}>
     */
    private function pendingQueue(Collection $accounts): Collection
    {
        return MailListMember::query()->pending()->with('mailList', 'addedBy')->orderBy('created_at')->get()
            ->groupBy('email')
            ->map(fn (Collection $members, string $email): array => [
                'email' => $email,
                'name' => $accounts->get($email)?->employee?->name,
                'lists' => $members->map(fn (MailListMember $member): string => (string) $member->mailList?->address)->all(),
                'by' => $members->first()->addedBy?->name,
            ])
            ->values();
    }

    /** mail-lists.confirm is one person's, never a role's: only a direct permission counts. */
    private function canConfirm(): bool
    {
        return $this->user()->hasDirectPermission('mail-lists.confirm');
    }

    private function authorizeConfirmer(): void
    {
        abort_unless($this->canConfirm(), 403);
    }

    private function authorizeManage(): void
    {
        abort_unless($this->user()->can('mail-lists.manage'), 403);
    }

    /** @param  Builder<MailListMember>  $query */
    private function markActive(Builder $query): void
    {
        $query->update([
            'status' => MailListMember::ACTIVE,
            'confirmed_by_user_id' => $this->user()->id,
            'confirmed_at' => now(),
        ]);
    }

    private function user(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
};
?>

@php
    $muted = 'text-secondary-medium dark:text-ink-muted';
@endphp

<x-desk.register class="@container" heading-id="hr-mail-lists-heading" :title="__('hr_mail_lists.title')" :count="$listCount" data-hr-mail-lists>
    <x-slot:search>
        <x-desk.register-search :label="__('hr_mail_lists.search')" :placeholder="__('hr_mail_lists.search_placeholder')" wire:model.live.debounce.300ms="search" />
    </x-slot:search>

    @if ($canManage)
        <x-slot:actions>
            <x-ui.button size="sm" icon="check-circle" :href="route('hr.mail-lists.check')" wire:navigate data-test="mail-lists-check">{{ __('hr_mail_lists.check.open') }}</x-ui.button>
        </x-slot:actions>
    @endif

    <x-slot:filters>
        <x-ui.header-filter-select :label="__('hr_mail_lists.groups')" wire:model.live="category" :value="$category">
            <option value="">{{ __('hr_mail_lists.cat.all') }}</option>
            @foreach ($categories as $key => $count)
                <option value="{{ $key }}">{{ __('hr_mail_lists.cat.'.$key) }} ({{ $count }})</option>
            @endforeach
        </x-ui.header-filter-select>
    </x-slot:filters>

    <p class="mb-4 text-sm {{ $muted }}">{{ __('hr_mail_lists.lead') }} {{ trans_choice('hr_mail_lists.summary', $people, ['lists' => $listCount, 'people' => $people]) }}</p>

    @if ($canConfirm)
        <x-ui.panel class="mb-6 p-4" data-mail-lists-pending>
            <h2 class="font-semibold">{{ __('hr_mail_lists.pending_title') }}</h2>
            <p class="mt-1 text-sm {{ $muted }}">{{ __('hr_mail_lists.pending_help') }}</p>
            @if ($pending->isEmpty())
                <p class="mt-3 text-sm {{ $muted }}">{{ __('hr_mail_lists.pending_none') }}</p>
            @else
                <ul class="mt-3 divide-y divide-panel-border">
                    @foreach ($pending as $person)
                        <li class="flex flex-wrap items-center justify-between gap-3 py-2.5" wire:key="pending-{{ $person['email'] }}" data-pending-email="{{ $person['email'] }}">
                            <div class="min-w-0">
                                <span class="block font-semibold" dir="auto">{{ $person['name'] ?? $person['email'] }}</span>
                                <span class="block text-xs {{ $muted }}" dir="ltr">{{ $person['email'] }}</span>
                                <span class="block text-xs {{ $muted }}">{{ __('hr_mail_lists.pending_lists') }}: <span dir="ltr">{{ implode(', ', $person['lists']) }}</span></span>
                            </div>
                            <x-ui.button tone="primary" size="sm" icon="check" wire:click="confirmEmail({{ \Illuminate\Support\Js::from($person['email']) }})" data-test="mail-lists-confirm">{{ __('hr_mail_lists.pending_done') }}</x-ui.button>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.panel>
    @endif

    @if ($listCount === 0)
        <x-ui.empty-state icon="envelope" :heading="__('hr_mail_lists.empty')" :body="__('hr_mail_lists.empty_help')" />
    @elseif ($rows->isEmpty())
        <x-ui.empty-state icon="magnifying-glass" :heading="__('hr_mail_lists.no_results')" :body="__('hr_mail_lists.no_results_help')" />
    @else
        <ul class="grid gap-3 @lg:grid-cols-2 @4xl:grid-cols-3">
            @foreach ($rows as $row)
                @php
                    $list = $row['list'];
                @endphp
                <li wire:key="mail-list-{{ $list->id }}" data-mail-list="{{ $list->address }}">
                    <x-ui.panel class="flex h-full flex-col gap-3 p-4">
                        <div class="min-w-0">
                            <h2 class="truncate font-semibold" dir="auto">{{ $list->name }}</h2>
                            <span class="block truncate text-sm {{ $muted }}" dir="ltr">{{ $list->address }}</span>
                            <span class="mt-1 block text-xs {{ $muted }}">{{ trans_choice('hr_mail_lists.members', $row['members']->count(), ['count' => $row['members']->count()]) }}</span>
                        </div>
                        @if ($row['matches']->isNotEmpty())
                            <p class="text-xs {{ $muted }}">{{ __('hr_mail_lists.matches') }}: <span dir="auto">{{ $row['matches']->map(fn (array $member): string => $member['name'] ?? $member['email'])->implode(', ') }}</span></p>
                        @endif
                        <div class="mt-auto flex flex-wrap gap-2">
                            <x-ui.button size="sm" icon="envelope" href="mailto:{{ $list->address }}">{{ __('hr_mail_lists.email_list') }}</x-ui.button>
                            <x-ui.button tone="ghost" size="sm" icon="users" wire:click="openList({{ $list->id }})" data-test="mail-list-open">{{ __('hr_mail_lists.members_button') }}</x-ui.button>
                        </div>
                    </x-ui.panel>
                </li>
            @endforeach
        </ul>
    @endif

    <x-ui.modal wire:model="showListModal" size="md" :title="$open ? $open['list']->name : __('hr_mail_lists.members_button')" :description="$open ? $open['list']->address : null" close="closeList">
        @if ($showListModal && $open)
            <div class="grid gap-y-4" data-mail-list-panel="{{ $open['list']->id }}">
                @if ($open['members']->isEmpty())
                    <p class="text-sm {{ $muted }}">{{ trans_choice('hr_mail_lists.members', 0, ['count' => 0]) }}</p>
                @else
                    <ul class="divide-y divide-panel-border">
                        @foreach ($open['members'] as $member)
                            <li class="flex items-center justify-between gap-3 py-2" wire:key="member-{{ $member['id'] }}" data-member="{{ $member['email'] }}">
                                <div class="min-w-0">
                                    <span class="block truncate font-semibold" dir="auto">{{ $member['name'] ?? $member['email'] }}</span>
                                    <span class="block truncate text-xs {{ $muted }}" dir="ltr">{{ $member['email'] }}</span>
                                    <span class="flex flex-wrap gap-1">
                                        @if ($member['pending'])
                                            <flux:badge size="sm" color="amber">{{ __('hr_mail_lists.pending_tag') }}</flux:badge>
                                        @endif
                                        @if ($member['former'])
                                            <flux:badge size="sm" color="zinc">{{ __('hr_mail_lists.former') }}</flux:badge>
                                        @endif
                                        @if ($member['external'])
                                            <flux:badge size="sm" color="sky">{{ __('hr_mail_lists.external') }}</flux:badge>
                                        @endif
                                    </span>
                                </div>
                                <div class="flex shrink-0 gap-1">
                                    @if ($canConfirm && $member['pending'])
                                        <x-ui.button tone="ghost" size="sm" icon="check" wire:click="confirmMember({{ $member['id'] }})" :aria-label="__('hr_mail_lists.pending_done')">{{ __('hr_mail_lists.pending_done_short') }}</x-ui.button>
                                    @endif
                                    @if ($canManage)
                                        <x-ui.button tone="ghost" size="sm" icon="trash" wire:click="removeMember({{ $member['id'] }})" wire:confirm="{{ __('hr_mail_lists.remove_confirm') }}" :aria-label="__('hr_mail_lists.remove')" data-test="mail-list-remove" />
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if ($canManage)
                    <form wire:submit="addMember" class="grid gap-y-2 border-t border-panel-border pt-4">
                        <x-ui.form-input type="email" wire:model="newEmail" :label="__('hr_mail_lists.add_member')" :help="__('hr_mail_lists.add_hint')" dir="ltr" />
                        <div class="flex flex-wrap justify-between gap-2">
                            <x-ui.button type="submit" size="sm" icon="plus" data-test="mail-list-add">{{ __('hr_mail_lists.add') }}</x-ui.button>
                            <x-ui.button tone="ghost" size="sm" icon="trash" wire:click="deleteList({{ $open['list']->id }})" wire:confirm="{{ __('hr_mail_lists.delete_confirm') }}" data-test="mail-list-delete">{{ __('hr_mail_lists.delete_list') }}</x-ui.button>
                        </div>
                    </form>
                @endif
            </div>
        @endif
    </x-ui.modal>
</x-desk.register>
