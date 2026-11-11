<?php

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Modules\Hr\Enums\News\NewsState;
use Modules\Hr\Models\News;
use Modules\Hr\Support\News\NewsFeed;

/**
 * One news item, announcement or event as a reader sees it. The same page
 * is the preview (hr.news.preview): whoever manages the item sees it there
 * before it goes out, with its state and audience above the text. Below,
 * a few other recent items this reader may see.
 */
new #[Layout('layouts::desk'), Title('hr_news.titles.show')] class extends Component {
    #[Locked]
    public int $newsId;

    #[Locked]
    public bool $preview = false;

    public function mount(News $news): void
    {
        $this->preview = request()->routeIs('hr.news.preview');
        Gate::authorize($this->preview ? 'manage' : 'view', $news);
        $this->newsId = (int) $news->id;
    }

    #[Computed]
    public function news(): News
    {
        return News::query()
            ->with(['tags:id,news_id,tag', 'audienceTargets:id,news_id,target_id'])
            ->findOrFail($this->newsId);
    }

    /**
     * @return Collection<int, News>
     */
    #[Computed]
    public function others(): Collection
    {
        return app(NewsFeed::class)->latestNews(auth()->user(), 4)
            ->reject(fn (News $item): bool => (int) $item->id === $this->newsId)
            ->take(3)
            ->values();
    }
}; ?>

@php
    $news = $this->news;
    $state = $news->state();
    $timing = $news->eventTiming();
    $cover = $news->coverUrl();
    $canManage = auth()->user()->can('manage', $news);
    $stateTone = ['draft' => 'dk-warn', 'scheduled' => 'dk-act', 'published' => 'dk-good', 'expired' => 'dk-mute', 'archived' => 'dk-mute'];
@endphp

<x-desk.register heading-id="hr-news-show-heading" :title="$news->localizedTitle()">
    <x-slot:actions>
        @if ($canManage)
            <a href="{{ route('hr.news.index') }}" wire:navigate class="dk-btn">{{ __('hr_news.show.all_items') }}</a>
            @can('update', $news)
                <a href="{{ route('hr.news.edit', $news) }}" wire:navigate class="dk-btn dk-primary"><x-desk.icon name="edit" />{{ __('common.edit') }}</a>
            @endcan
        @endif
    </x-slot:actions>

    <article class="grid max-w-3xl gap-6" dir="{{ $news->textDirection() }}">
        @if ($preview || $canManage)
            <div class="flex flex-wrap items-center gap-2 text-sm" data-test="hr-news-manage-strip">
                <span class="dk-pill {{ $stateTone[$state->value] ?? 'dk-mute' }}">{{ __('hr_news.states.'.$state->value) }}</span>
                <span class="text-secondary-medium dark:text-ink-muted">
                    {{ __('hr_news.audience.'.$news->audience_type->value) }}@if ($news->audience_type->needsTargets()): {{ $news->audienceEntities()->pluck('name')->filter()->take(5)->implode('، ') }}@endif
                </span>
                @if ($preview && ! in_array($state, [NewsState::Published, NewsState::Expired], true))
                    <span class="text-secondary-medium dark:text-ink-muted">· {{ __('hr_news.show.preview_note') }}</span>
                @endif
            </div>
        @endif

        <p class="type-meta flex flex-wrap gap-x-3 text-secondary-medium dark:text-ink-muted">
            <span>{{ __('hr_news.types.'.$news->type->value) }}</span>
            @if ($news->published_at)
                <span class="tabular-nums">{{ $news->published_at->translatedFormat(__('formats.date')) }}</span>
            @endif
        </p>

        @if ($cover !== null)
            <img src="{{ $cover }}" alt="" class="max-h-96 w-full rounded-xl object-cover" />
        @endif

        @if ($news->isEvent() && $news->event_starts_at)
            <div class="rounded-lg border border-secondary-soft p-4 dark:border-line" data-test="hr-news-event">
                <p class="font-semibold">{{ __('hr_news.show.event_when') }}</p>
                <p class="tabular-nums">
                    {{ $news->event_starts_at->translatedFormat(__('formats.datetime')) }}
                    @if ($news->event_ends_at) — {{ $news->event_ends_at->translatedFormat(__('formats.datetime')) }}@endif
                </p>
                @if ($timing !== null)
                    <p class="type-meta text-secondary-medium dark:text-ink-muted">{{ __('hr_news.show.timing.'.$timing) }}</p>
                @endif
            </div>
        @endif

        <div class="whitespace-pre-line leading-relaxed">{{ $news->localizedDescription() }}</div>

        @if ($news->tags->isNotEmpty())
            <p class="flex flex-wrap gap-2">
                @foreach ($news->tags as $tag)
                    <span class="dk-pill dk-mute">{{ $tag->tag }}</span>
                @endforeach
            </p>
        @endif
    </article>

    @if ($this->others->isNotEmpty())
        <section class="mt-10 max-w-3xl" aria-labelledby="hr-news-more">
            <h2 id="hr-news-more" class="mb-3 font-semibold">{{ __('hr_news.show.more') }}</h2>
            <ul class="grid gap-2">
                @foreach ($this->others as $item)
                    <li wire:key="other-{{ $item->id }}">
                        <a href="{{ route('hr.news.show', $item) }}" wire:navigate class="hover:underline" dir="auto">{{ $item->localizedTitle() }}</a>
                        <span class="type-meta text-secondary-medium dark:text-ink-muted tabular-nums">· {{ $item->published_at?->translatedFormat(__('formats.date_short')) }}</span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</x-desk.register>
