<?php

use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Hr\Actions\News\ChangeNewsState;
use Modules\Hr\Enums\News\NewsState;
use Modules\Hr\Enums\News\NewsType;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\News;
use Modules\Hr\Support\Desk\HrViewer;

/**
 * News and events: the management list. HR, the general manager and
 * administrators see every item; a manager sees the ones they created.
 * Rows are narrowed by state (draft, scheduled, published, expired,
 * archived), type, tag and a search over both titles. The one-step changes
 * (publish now, back to draft, archive, restore, delete) go through
 * ChangeNewsState, each behind NewsPolicy.
 */
new #[Layout('layouts::desk'), Title('hr_news.titles.index')] class extends Component {
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $state = '';

    #[Url(except: '')]
    public string $type = '';

    #[Url(except: '')]
    public string $tag = '';

    public ?int $deletingId = null;

    public bool $showDeleteModal = false;

    public function mount(): void
    {
        Gate::authorize('viewAny', News::class);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'state', 'type', 'tag'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'state', 'type', 'tag');
        $this->resetPage();
    }

    public function publishNow(int $id): void
    {
        $this->change($id, 'update', fn (ChangeNewsState $change, News $news): bool => $change->publishNow(auth()->user(), $news), 'hr_news.list.published_now');
    }

    public function unpublish(int $id): void
    {
        $this->change($id, 'update', fn (ChangeNewsState $change, News $news): bool => $change->unpublish(auth()->user(), $news), 'hr_news.list.unpublished');
    }

    public function archive(int $id): void
    {
        $this->change($id, 'update', fn (ChangeNewsState $change, News $news): bool => $change->archive(auth()->user(), $news), 'hr_news.list.archived');
    }

    public function restore(int $id): void
    {
        $this->change($id, 'update', fn (ChangeNewsState $change, News $news): bool => $change->restore(auth()->user(), $news), 'hr_news.list.restored');
    }

    public function confirmDelete(int $id): void
    {
        Gate::authorize('delete', $this->scoped()->findOrFail($id));
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function cancelDelete(): void
    {
        $this->deletingId = null;
        $this->showDeleteModal = false;
    }

    public function delete(): void
    {
        if ($this->deletingId === null) {
            return;
        }

        $news = $this->scoped()->findOrFail($this->deletingId);
        Gate::authorize('delete', $news);
        app(ChangeNewsState::class)->delete($news);
        $this->cancelDelete();
        unset($this->rows, $this->counts);
        Flux::toast(variant: 'success', text: __('hr_news.list.deleted'));
    }

    /**
     * @param  Closure(ChangeNewsState, News): bool  $apply
     */
    private function change(int $id, string $ability, Closure $apply, string $message): void
    {
        $news = $this->scoped()->findOrFail($id);
        Gate::authorize($ability, $news);

        $done = $apply(app(ChangeNewsState::class), $news);
        unset($this->rows, $this->counts);
        Flux::toast(variant: $done ? 'success' : 'warning', text: __($done ? $message : 'hr_news.list.not_allowed_now'));
    }

    /**
     * The items this person manages: all of them, or (a manager) their own.
     *
     * @return Builder<News>
     */
    private function scoped(): Builder
    {
        $viewer = HrViewer::for(auth()->user());

        return News::query()->when(! $viewer->isHr() && ! $viewer->seesOrganisation(), fn (Builder $query) => $query->where('news.created_by', auth()->id()));
    }

    /**
     * @return Builder<News>
     */
    private function filtered(bool $withState = true): Builder
    {
        $term = trim($this->search);

        return $this->scoped()
            ->when($withState && NewsState::tryFrom($this->state) !== null, fn (Builder $query) => $query->inState($this->state))
            ->when(NewsType::tryFrom($this->type) !== null, fn (Builder $query) => $query->ofType($this->type))
            ->when(trim($this->tag) !== '', fn (Builder $query) => $query->tagged($this->tag))
            ->when($term !== '', fn (Builder $query) => $query->where(fn (Builder $title) => $title
                ->where('news.title_ar', 'like', '%'.App\Support\Like::escape($term).'%')
                ->orWhere('news.title_en', 'like', '%'.App\Support\Like::escape($term).'%')));
    }

    #[Computed]
    public function rows(): LengthAwarePaginator
    {
        return $this->filtered()
            ->select(['news.id', 'news.type', 'news.status', 'news.title_en', 'news.title_ar', 'news.audience_type', 'news.published_at', 'news.display_until', 'news.event_starts_at', 'news.event_ends_at', 'news.created_by'])
            ->with(['tags:id,news_id,tag', 'audienceTargets:id,news_id,target_id', 'creator:id,email,employee_id', 'creator.employee:'.implode(',', Employee::NameColumns)])
            ->orderByDesc('news.id')
            ->paginate(15);
    }

    /**
     * How many items sit in each state under the other filters.
     *
     * @return array<string, int>
     */
    #[Computed]
    public function counts(): array
    {
        $counts = [];

        foreach (NewsState::cases() as $case) {
            $counts[$case->value] = $this->filtered(false)->inState($case)->count();
        }

        return $counts;
    }

    /**
     * @return list<string>
     */
    #[Computed]
    public function tags(): array
    {
        return News::popularTags(30);
    }

    public function hasFilters(): bool
    {
        return trim($this->search) !== '' || $this->state !== '' || $this->type !== '' || $this->tag !== '';
    }
}; ?>

@php
    $rows = $this->rows;
    $canCreate = auth()->user()->can('create', News::class);
    $stateTone = ['draft' => 'dk-warn', 'scheduled' => 'dk-act', 'published' => 'dk-good', 'expired' => 'dk-mute', 'archived' => 'dk-mute'];
@endphp

<x-desk.register class="@container/directory" heading-id="hr-news-heading" :title="__('hr_news.titles.index')" :count="$rows->total()">
    <x-slot:actions>
        @if ($canCreate)
            <a href="{{ route('hr.news.create') }}" wire:navigate class="dk-btn dk-primary" data-test="hr-news-create"><x-desk.icon name="plus" />{{ __('hr_news.list.create') }}</a>
        @endif
    </x-slot:actions>

    <x-slot:search>
        <x-desk.register-search :label="__('hr_news.list.search')" wire:model.live.debounce.300ms="search" />
    </x-slot:search>

    <x-slot:filters>
        <x-ui.header-filter-select :label="__('hr_news.list.columns.status')" wire:model.live="state" :value="$state">
            <option value="">{{ __('hr_news.list.all_states') }}</option>
            @foreach (NewsState::cases() as $case)
                <option value="{{ $case->value }}">{{ __('hr_news.states.'.$case->value) }} ({{ $this->counts[$case->value] ?? 0 }})</option>
            @endforeach
        </x-ui.header-filter-select>

        <x-ui.header-filter-select :label="__('hr_news.list.columns.type')" wire:model.live="type" :value="$type">
            <option value="">{{ __('hr_news.list.all_types') }}</option>
            @foreach (NewsType::cases() as $case)
                <option value="{{ $case->value }}">{{ __('hr_news.types.'.$case->value) }}</option>
            @endforeach
        </x-ui.header-filter-select>

        @if ($this->tags !== [])
            <x-ui.header-filter-select :label="__('hr_news.list.columns.tags')" wire:model.live="tag" :value="$tag" searchable>
                <option value="">{{ __('hr_news.list.all_tags') }}</option>
                @foreach ($this->tags as $option)
                    <option value="{{ $option }}">{{ $option }}</option>
                @endforeach
            </x-ui.header-filter-select>
        @endif
    </x-slot:filters>

    @if ($rows->isEmpty())
        <x-ui.empty-state icon="newspaper" :heading="$this->hasFilters() ? __('hr_news.list.empty_filtered') : __('hr_news.list.empty')">
            <x-slot:action>
                @if ($this->hasFilters())
                    <x-ui.button tone="ghost" size="sm" wire:click="resetFilters">{{ __('common.reset_filters') }}</x-ui.button>
                @elseif ($canCreate)
                    <a href="{{ route('hr.news.create') }}" wire:navigate class="dk-btn dk-primary">{{ __('hr_news.list.create') }}</a>
                @endif
            </x-slot:action>
        </x-ui.empty-state>
    @else
        <div wire:loading.class="opacity-50" wire:target="search, state, type, tag, resetFilters, previousPage, nextPage, gotoPage">
            <x-ui.data-table :caption="__('hr_news.titles.index')">
                <x-slot:head>
                    <th>{{ __('hr_news.list.columns.title') }}</th>
                    <th class="w-28!">{{ __('hr_news.list.columns.type') }}</th>
                    <th class="w-44!">{{ __('hr_news.list.columns.audience') }}</th>
                    <th class="w-36!">{{ __('hr_news.list.columns.publish_at') }}</th>
                    <th class="w-36!">{{ __('hr_news.list.columns.event_date') }}</th>
                    <th class="w-28!">{{ __('hr_news.list.columns.status') }}</th>
                    <th class="w-36!">{{ __('hr_news.list.columns.author') }}</th>
                    <th class="w-12!"><span class="sr-only">{{ __('hr_news.list.columns.actions') }}</span></th>
                </x-slot:head>

                @foreach ($rows as $news)
                    @php
                        $itemState = $news->state();
                        $released = in_array($itemState, [NewsState::Published, NewsState::Expired], true);
                        $href = $released ? route('hr.news.show', $news) : route('hr.news.preview', $news);
                        $canUpdate = auth()->user()->can('update', $news);
                    @endphp
                    <tr wire:key="news-{{ $news->id }}">
                        <x-ui.data-table.cell>
                            <a href="{{ $href }}" wire:navigate class="font-semibold text-secondary-dark hover:underline dark:text-ink" dir="auto">{{ $news->localizedTitle() }}</a>
                            @if ($news->tags->isNotEmpty())
                                <p class="type-meta mt-0.5 text-secondary-medium dark:text-ink-muted">{{ $news->tags->pluck('tag')->implode(' · ') }}</p>
                            @endif
                        </x-ui.data-table.cell>
                        <x-ui.data-table.cell :label="__('hr_news.list.columns.type')">{{ __('hr_news.types.'.$news->type->value) }}</x-ui.data-table.cell>
                        <x-ui.data-table.cell :label="__('hr_news.list.columns.audience')">
                            {{ __('hr_news.audience.'.$news->audience_type->value) }}@if ($news->audience_type->needsTargets()) <span class="tabular-nums text-secondary-medium dark:text-ink-muted">({{ $news->audienceTargets->count() }})</span>@endif
                        </x-ui.data-table.cell>
                        <x-ui.data-table.cell :label="__('hr_news.list.columns.publish_at')" numeric>{{ $news->published_at?->translatedFormat(__('formats.date_short')) ?? '—' }}@if ($news->published_at) · {{ $news->published_at->format('H:i') }}@endif</x-ui.data-table.cell>
                        <x-ui.data-table.cell :label="__('hr_news.list.columns.event_date')" numeric>{{ $news->event_starts_at?->translatedFormat(__('formats.date_short')) ?? '—' }}</x-ui.data-table.cell>
                        <x-ui.data-table.cell :label="__('hr_news.list.columns.status')"><span class="dk-pill {{ $stateTone[$itemState->value] ?? 'dk-mute' }}">{{ __('hr_news.states.'.$itemState->value) }}</span></x-ui.data-table.cell>
                        <x-ui.data-table.cell :label="__('hr_news.list.columns.author')">{{ $news->creator?->employee?->name ?? $news->creator?->email ?? '—' }}</x-ui.data-table.cell>
                        <x-ui.data-table.cell class="@3xl:text-end">
                            <flux:dropdown position="bottom" align="end">
                                <flux:button size="xs" variant="ghost" icon="ellipsis-horizontal" :aria-label="__('hr_news.list.columns.actions')" />
                                <flux:menu>
                                    <flux:menu.item icon="eye" :href="$href" wire:navigate>{{ $released ? __('hr_news.list.open') : __('hr_news.list.preview') }}</flux:menu.item>
                                    @if ($canUpdate)
                                        <flux:menu.item icon="pencil-square" :href="route('hr.news.edit', $news)" wire:navigate>{{ __('common.edit') }}</flux:menu.item>
                                        @if (in_array($itemState, [NewsState::Draft, NewsState::Scheduled], true))
                                            <flux:menu.item icon="paper-airplane" wire:click="publishNow({{ $news->id }})">{{ __('hr_news.list.publish_now') }}</flux:menu.item>
                                        @endif
                                        @if (in_array($itemState, [NewsState::Published, NewsState::Scheduled, NewsState::Expired], true))
                                            <flux:menu.item icon="arrow-uturn-left" wire:click="unpublish({{ $news->id }})">{{ __('hr_news.list.unpublish') }}</flux:menu.item>
                                        @endif
                                        @if ($itemState === NewsState::Archived)
                                            <flux:menu.item icon="arrow-path" wire:click="restore({{ $news->id }})">{{ __('hr_news.list.restore') }}</flux:menu.item>
                                        @else
                                            <flux:menu.item icon="archive-box" wire:click="archive({{ $news->id }})">{{ __('hr_news.list.archive') }}</flux:menu.item>
                                        @endif
                                    @endif
                                    @can('delete', $news)
                                        <flux:menu.separator />
                                        <flux:menu.item icon="trash" variant="danger" wire:click="confirmDelete({{ $news->id }})">{{ __('common.delete') }}</flux:menu.item>
                                    @endcan
                                </flux:menu>
                            </flux:dropdown>
                        </x-ui.data-table.cell>
                    </tr>
                @endforeach
            </x-ui.data-table>
        </div>

        <flux:pagination :paginator="$rows" />
    @endif

    <x-ui.confirm-modal
        wire:model="showDeleteModal"
        close="cancelDelete"
        :title="__('hr_news.list.delete_title')"
        :message="__('hr_news.list.delete_body')"
        confirm="delete"
        :confirm-label="__('common.delete')"
    />
</x-desk.register>
