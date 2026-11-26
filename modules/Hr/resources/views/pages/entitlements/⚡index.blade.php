<?php

use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Modules\Core\Models\User;
use Modules\Hr\Models\EmployeeEntitlement;
use Modules\Hr\Models\Entitlement;
use Modules\Hr\Models\JobLevel;
use Modules\Hr\Models\MailList;
use Modules\Hr\Models\Position;
use Modules\Hr\Models\PositionEntitlement;
use Modules\Hr\Support\Employees\HrAccess;
use Modules\Hr\Support\Entitlements\Entitlements;

/*
 * The Permissions Library (meeting 19/11, track 5): the items and the
 * positions (or job levels) that need them by default. HR roles and the
 * administrators, with entitlements.manage-library (route, mount and every
 * action). A city HR links and unlinks only positions of its own cities
 * (Position::inCity); a job-level default applies everywhere, so only those
 * who are not limited to cities set or remove one.
 */
new #[Layout('layouts::desk'), Title('hr_entitlements.title')] class extends Component {
    /** @var array{id: int|null, name_en: string, name_ar: string, category: string, reference: string, mail_list_id: string|int|null, description: string, is_active: bool} */
    public array $item = [];

    public bool $editing = false;

    #[Locked]
    public ?int $openId = null;

    public string $positionSearch = '';

    public string $jobLevelId = '';

    public function mount(): void
    {
        $this->authorizeLibrary();
        $this->resetItem();
    }

    /** @return Collection<int, Entitlement> */
    #[Computed]
    public function items(): Collection
    {
        return Entitlement::query()
            ->withCount(['defaults', 'assignments as open_count' => fn (Builder $query) => $query->open()])
            ->with('mailList')
            ->orderByDesc('is_active')
            ->orderBy(Entitlement::translatedColumn('name'))
            ->get();
    }

    #[Computed]
    public function open(): ?Entitlement
    {
        return $this->openId === null ? null : $this->items->firstWhere('id', $this->openId);
    }

    /** @return Collection<int, PositionEntitlement> */
    #[Computed]
    public function links(): Collection
    {
        return $this->open === null
            ? new Collection
            : PositionEntitlement::query()->where('entitlement_id', $this->open->id)->with(['position.center', 'position.city', 'jobLevel'])->get();
    }

    /** @return Collection<int, Position> */
    #[Computed]
    public function matches(): Collection
    {
        $term = trim($this->positionSearch);

        if ($this->open === null || mb_strlen($term) < 2) {
            return new Collection;
        }

        return $this->positionsInScope()
            ->search($term)
            ->whereNotIn('id', $this->links->pluck('position_id')->filter()->all())
            ->with(['center', 'city'])
            ->orderBy(Position::translatedColumn('title'))
            ->limit(15)
            ->get();
    }

    /** @return Collection<int, MailList> */
    #[Computed]
    public function mailLists(): Collection
    {
        return MailList::query()->orderBy('name')->get(['id', 'name', 'address']);
    }

    /** @return Collection<int, JobLevel> */
    #[Computed]
    public function jobLevels(): Collection
    {
        return JobLevel::query()->orderBy('grade')->get();
    }

    #[Computed]
    public function canLinkJobLevels(): bool
    {
        return app(Entitlements::class)->canLinkJobLevels(Auth::user());
    }

    /**
     * The links of the open item this login may remove.
     *
     * @return list<int>
     */
    #[Computed]
    public function touchableLinkIds(): array
    {
        return $this->links
            ->filter(fn (PositionEntitlement $link): bool => $this->mayTouchLink($link))
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->values()
            ->all();
    }

    public function newItem(): void
    {
        $this->authorizeLibrary();
        $this->resetItem();
        $this->resetValidation();
        $this->editing = true;
    }

    public function edit(int $id): void
    {
        $this->authorizeLibrary();
        $model = Entitlement::query()->findOrFail($id);

        $this->item = [
            'id' => $model->id,
            'name_en' => (string) $model->name_en,
            'name_ar' => (string) $model->name_ar,
            'category' => (string) $model->category,
            'reference' => (string) $model->reference,
            'mail_list_id' => $model->mail_list_id,
            'description' => (string) $model->description,
            'is_active' => (bool) $model->is_active,
        ];
        $this->resetValidation();
        $this->editing = true;
    }

    public function cancelEdit(): void
    {
        $this->resetItem();
        $this->resetValidation();
        $this->editing = false;
    }

    public function save(): void
    {
        $this->authorizeLibrary();

        $data = $this->validate([
            'item.name_en' => ['required', 'string', 'max:255'],
            'item.name_ar' => ['required', 'string', 'max:255'],
            'item.category' => ['required', Rule::in(Entitlement::CATEGORIES)],
            'item.reference' => ['nullable', 'string', 'max:255'],
            'item.mail_list_id' => ['nullable', 'integer', 'exists:mail_lists,id'],
            'item.description' => ['nullable', 'string', 'max:2000'],
            'item.is_active' => ['boolean'],
        ])['item'];

        $data['mail_list_id'] = $data['category'] === 'mail_list' && filled($data['mail_list_id'] ?? null) ? (int) $data['mail_list_id'] : null;
        $data['reference'] = filled($data['reference'] ?? null) ? $data['reference'] : null;
        $data['description'] = filled($data['description'] ?? null) ? $data['description'] : null;

        $model = filled($this->item['id']) ? Entitlement::query()->findOrFail($this->item['id']) : new Entitlement;
        $model->fill($data)->save();

        $this->openId = $model->id;
        $this->cancelEdit();
        $this->flush();
        Flux::toast(variant: 'success', text: __('hr_entitlements.saved'));
    }

    public function toggleActive(int $id): void
    {
        $this->authorizeLibrary();
        $model = Entitlement::query()->findOrFail($id);
        $model->update(['is_active' => ! $model->is_active]);

        $this->flush();
        Flux::toast(variant: 'success', text: $model->is_active ? __('hr_entitlements.activated') : __('hr_entitlements.deactivated'));
    }

    /** Only an item nobody has ever held: anything else is switched off, so the history stays. */
    public function deleteItem(int $id): void
    {
        $this->authorizeLibrary();
        $model = Entitlement::query()->findOrFail($id);

        if (EmployeeEntitlement::query()->where('entitlement_id', $model->id)->exists()) {
            Flux::toast(variant: 'danger', text: __('hr_entitlements.cannot_delete'));

            return;
        }

        // Its defaults go with it; a city HR may not remove another city's links this way.
        $foreign = $model->defaults()->get()->contains(fn (PositionEntitlement $link): bool => ! $this->mayTouchLink($link));

        if ($foreign) {
            Flux::toast(variant: 'danger', text: __('hr_entitlements.cannot_delete_linked'));

            return;
        }

        $model->delete();
        $this->openId = $this->openId === $id ? null : $this->openId;
        $this->flush();
        Flux::toast(variant: 'success', text: __('hr_entitlements.deleted'));
    }

    public function openItem(int $id): void
    {
        $this->authorizeLibrary();
        $this->openId = Entitlement::query()->findOrFail($id)->id;
        $this->positionSearch = '';
        $this->jobLevelId = '';
        $this->flush();
    }

    public function closeItem(): void
    {
        $this->openId = null;
        $this->flush();
    }

    public function addPosition(int $positionId): void
    {
        $this->authorizeLibrary();
        $entitlement = $this->openEntitlement();

        $position = $this->positionsInScope()->whereKey($positionId)->firstOrFail();

        PositionEntitlement::query()->firstOrCreate(['entitlement_id' => $entitlement->id, 'position_id' => $position->id]);
        $this->positionSearch = '';
        $this->flush();
        Flux::toast(variant: 'success', text: __('hr_entitlements.linked'));
    }

    public function addJobLevel(): void
    {
        $this->authorizeLibrary();
        abort_unless(app(Entitlements::class)->canLinkJobLevels(Auth::user()), 403);
        $entitlement = $this->openEntitlement();

        $level = JobLevel::query()->findOrFail((int) $this->jobLevelId);

        PositionEntitlement::query()->firstOrCreate(['entitlement_id' => $entitlement->id, 'job_level_id' => $level->id]);
        $this->jobLevelId = '';
        $this->flush();
        Flux::toast(variant: 'success', text: __('hr_entitlements.linked'));
    }

    public function removeLink(int $linkId): void
    {
        $this->authorizeLibrary();
        $entitlement = $this->openEntitlement();

        $link = PositionEntitlement::query()->where('entitlement_id', $entitlement->id)->findOrFail($linkId);
        abort_unless($this->mayTouchLink($link), 403);

        $link->delete();
        $this->flush();
        Flux::toast(variant: 'success', text: __('hr_entitlements.unlinked'));
    }

    private function authorizeLibrary(): void
    {
        abort_unless(app(Entitlements::class)->canManageLibrary(Auth::user()), 403);
    }

    private function openEntitlement(): Entitlement
    {
        abort_if($this->openId === null, 404);

        return Entitlement::query()->findOrFail($this->openId);
    }

    /** @return Builder<Position> */
    private function positionsInScope(): Builder
    {
        $cityIds = HrAccess::cityIds(Auth::user());

        return Position::query()->when($cityIds !== null, fn (Builder $query) => $query->inCity($cityIds));
    }

    private function mayTouchLink(PositionEntitlement $link): bool
    {
        if ($link->job_level_id !== null) {
            return app(Entitlements::class)->canLinkJobLevels(Auth::user());
        }

        return $this->positionsInScope()->whereKey($link->position_id)->exists();
    }

    /** Forget what the page read, so the next render reads the changes. */
    private function flush(): void
    {
        unset($this->items, $this->open, $this->links, $this->matches, $this->touchableLinkIds);
    }

    private function resetItem(): void
    {
        $this->item = [
            'id' => null, 'name_en' => '', 'name_ar' => '', 'category' => 'other',
            'reference' => '', 'mail_list_id' => null, 'description' => '', 'is_active' => true,
        ];
    }
};
?>

@php
    $items = $this->items;
    $open = $this->open;
    $muted = 'text-secondary-medium dark:text-ink-muted';
@endphp

<section class="flex flex-col gap-5" data-entitlements-library>
    <x-ui.detail-header :breadcrumbs="[['label' => __('hr_entitlements.title')]]" :title="__('hr_entitlements.title')" :subtitle="__('hr_entitlements.lead')">
        <x-slot:actions>
            @unless ($editing)
                <x-ui.button tone="primary" icon="plus" wire:click="newItem" data-test="entitlement-new">{{ __('hr_entitlements.new') }}</x-ui.button>
            @endunless
        </x-slot:actions>
    </x-ui.detail-header>

    @if ($editing)
        <x-ui.panel :title="$item['id'] ? __('hr_entitlements.edit') : __('hr_entitlements.new')" data-entitlement-form>
            <form wire:submit="save" class="grid gap-x-4 gap-y-3 p-4 sm:grid-cols-2">
                <x-ui.form-input wire:model="item.name_en" :label="__('hr_entitlements.name_en')" maxlength="255" dir="ltr" required />
                <x-ui.form-input wire:model="item.name_ar" :label="__('hr_entitlements.name_ar')" maxlength="255" dir="rtl" required />
                <x-ui.form-select wire:model.live="item.category" :label="__('hr_entitlements.category')" required>
                    @foreach (Entitlement::CATEGORIES as $category)
                        <option value="{{ $category }}">{{ __('hr_entitlements.categories.'.$category) }}</option>
                    @endforeach
                </x-ui.form-select>
                @if ($item['category'] === 'mail_list')
                    <x-ui.form-select wire:model="item.mail_list_id" :label="__('hr_entitlements.mail_list')">
                        <option value="">—</option>
                        @foreach ($this->mailLists as $list)
                            <option value="{{ $list->id }}">{{ $list->name }} ({{ $list->address }})</option>
                        @endforeach
                    </x-ui.form-select>
                @endif
                <x-ui.form-input wire:model="item.reference" :label="__('hr_entitlements.reference')" :help="__('hr_entitlements.reference_hint')" maxlength="255" dir="ltr" />
                <x-ui.form-textarea wire:model="item.description" :label="__('hr_entitlements.description')" rows="2" class="sm:col-span-2" />
                <flux:checkbox wire:model="item.is_active" :label="__('hr_entitlements.active')" class="sm:col-span-2" />
                <div class="flex flex-wrap gap-2 sm:col-span-2">
                    <x-ui.button type="submit" tone="primary" data-test="entitlement-save">{{ __('hr_entitlements.save') }}</x-ui.button>
                    <x-ui.button type="button" wire:click="cancelEdit">{{ __('hr_entitlements.cancel') }}</x-ui.button>
                </div>
            </form>
        </x-ui.panel>
    @endif

    @if ($items->isEmpty())
        <x-ui.empty-state icon="key" :heading="__('hr_entitlements.empty')" :body="__('hr_entitlements.empty_help')" />
    @else
        <ul class="flex flex-col gap-3" data-entitlement-list>
            @foreach ($items as $entry)
                <li wire:key="entitlement-{{ $entry->id }}">
                    <x-ui.panel>
                        <div class="flex flex-wrap items-start justify-between gap-3 p-4">
                            <div class="min-w-0">
                                <h2 class="flex flex-wrap items-center gap-2 font-semibold">
                                    <span dir="auto">{{ $entry->name }}</span>
                                    <span class="inline-flex items-center rounded-md bg-secondary-soft px-2 py-0.5 text-xs font-semibold text-secondary-dark dark:bg-surface-2 dark:text-ink">{{ __('hr_entitlements.categories.'.$entry->category) }}</span>
                                    @unless ($entry->is_active)
                                        <span class="inline-flex items-center rounded-md bg-status-critical-soft px-2 py-0.5 text-xs font-semibold text-status-critical">{{ __('hr_entitlements.inactive') }}</span>
                                    @endunless
                                </h2>
                                <p class="mt-1 text-sm {{ $muted }}">
                                    {{ trans_choice('hr_entitlements.defaults_count', $entry->defaults_count, ['count' => $entry->defaults_count]) }}
                                    · {{ trans_choice('hr_entitlements.holders_count', $entry->open_count, ['count' => $entry->open_count]) }}
                                    @if ($entry->mailList)
                                        · <span dir="ltr">{{ $entry->mailList->address }}</span>
                                    @elseif ($entry->reference)
                                        · <span dir="ltr">{{ $entry->reference }}</span>
                                    @endif
                                </p>
                                @if ($entry->description)
                                    <p class="mt-1 text-sm {{ $muted }}">{{ $entry->description }}</p>
                                @endif
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                @if ($open?->id === $entry->id)
                                    <x-ui.button tone="ghost" size="sm" wire:click="closeItem">{{ __('hr_entitlements.hide_defaults') }}</x-ui.button>
                                @else
                                    <x-ui.button size="sm" wire:click="openItem({{ $entry->id }})" data-test="entitlement-open-{{ $entry->id }}">{{ __('hr_entitlements.defaults') }}</x-ui.button>
                                @endif
                                <x-ui.button tone="ghost" size="sm" icon="pencil-square" wire:click="edit({{ $entry->id }})">{{ __('hr_entitlements.edit_action') }}</x-ui.button>
                                <x-ui.button tone="ghost" size="sm" wire:click="toggleActive({{ $entry->id }})">{{ $entry->is_active ? __('hr_entitlements.deactivate') : __('hr_entitlements.activate') }}</x-ui.button>
                                @if ($entry->open_count === 0)
                                    <x-ui.confirm-action :title="__('hr_entitlements.delete_confirm')" confirm="deleteItem({{ $entry->id }})" :confirm-label="__('hr_entitlements.delete_action')" tone="danger">
                                        <x-ui.button tone="ghost" size="sm" icon="trash">{{ __('hr_entitlements.delete_action') }}</x-ui.button>
                                    </x-ui.confirm-action>
                                @endif
                            </div>
                        </div>

                        @if ($open?->id === $entry->id)
                            @php
                                $touchable = $this->touchableLinkIds;
                            @endphp
                            <div class="border-t border-panel-border p-4" data-entitlement-defaults>
                                <h3 class="font-semibold">{{ __('hr_entitlements.defaults') }}</h3>
                                <p class="text-sm {{ $muted }}">{{ __('hr_entitlements.defaults_help') }}</p>

                                @if ($this->links->isEmpty())
                                    <p class="mt-2 text-sm {{ $muted }}">{{ __('hr_entitlements.no_defaults') }}</p>
                                @else
                                    <ul class="mt-2 divide-y divide-panel-border">
                                        @foreach ($this->links as $link)
                                            <li wire:key="link-{{ $link->id }}" class="flex flex-wrap items-center justify-between gap-2 py-2">
                                                <span class="min-w-0">
                                                    @if ($link->jobLevel)
                                                        <span class="inline-flex items-center rounded-md bg-secondary-soft px-2 py-0.5 text-xs font-semibold text-secondary-dark dark:bg-surface-2 dark:text-ink">{{ __('hr_entitlements.job_level') }}</span>
                                                        <span dir="auto">{{ $link->jobLevel->name }}</span>
                                                    @elseif ($link->position)
                                                        <span dir="auto">{{ $link->position->title }}</span>
                                                        <span class="text-sm {{ $muted }}">· {{ $link->position->center?->name ?? $link->position->city?->name }}</span>
                                                    @endif
                                                </span>
                                                @if (in_array($link->id, $touchable, true))
                                                    <x-ui.button tone="ghost" size="sm" wire:click="removeLink({{ $link->id }})">{{ __('hr_entitlements.unlink') }}</x-ui.button>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif

                                <div class="mt-3 grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <x-ui.form-input wire:model.live.debounce.300ms="positionSearch" :label="__('hr_entitlements.add_position')" :placeholder="__('hr_entitlements.search_position')" />
                                        @if ($this->matches->isNotEmpty())
                                            <ul class="mt-2 divide-y divide-panel-border rounded-lg border border-panel-border">
                                                @foreach ($this->matches as $position)
                                                    <li wire:key="match-{{ $position->id }}" class="flex items-center justify-between gap-2 px-3 py-2">
                                                        <span class="min-w-0"><span dir="auto">{{ $position->title }}</span>
                                                            <span class="text-sm {{ $muted }}">· {{ $position->center?->name ?? $position->city?->name }}</span></span>
                                                        <x-ui.button size="sm" wire:click="addPosition({{ $position->id }})">{{ __('hr_entitlements.link') }}</x-ui.button>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @elseif (mb_strlen(trim($positionSearch)) >= 2)
                                            <p class="mt-2 text-sm {{ $muted }}">{{ __('hr_entitlements.no_positions') }}</p>
                                        @endif
                                    </div>
                                    @if ($this->canLinkJobLevels)
                                        <div class="flex flex-col gap-2">
                                            <x-ui.form-select wire:model="jobLevelId" :label="__('hr_entitlements.add_job_level')" :help="__('hr_entitlements.job_level_help')">
                                                <option value="">—</option>
                                                @foreach ($this->jobLevels as $level)
                                                    <option value="{{ $level->id }}">{{ $level->name }}</option>
                                                @endforeach
                                            </x-ui.form-select>
                                            <div>
                                                <x-ui.button size="sm" wire:click="addJobLevel" data-test="entitlement-add-job-level">{{ __('hr_entitlements.link') }}</x-ui.button>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </x-ui.panel>
                </li>
            @endforeach
        </ul>
    @endif
</section>
