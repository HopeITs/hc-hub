@php
    use App\Models\News;
    use Carbon\Carbon;

    $today = Carbon::today();

    $newsItems = News::with('newsable')
        ->whereDate('event_date', '<', $today)
        ->whereDate('event_date', '>=', $today->copy()->subDays(7))
        ->orderByDesc('event_date')
        ->get();
@endphp



<div x-data="{ open: false, selected: null }"
    x-on:keydown.escape.window="open = false"
    class="relative w-full h-full  py-6 bg-white rounded-3xl shadow-[0px_0px_25px_-14px_rgba(0,_0,_0,_0.30)] flex flex-col overflow-hidden">
    <div class="flex items-center justify-between px-6 pt-5 pb-1 flex-shrink-0">
        <div class="flex items-center gap-3">
            <h2 class="text-md font-base text-[#58595B]">
                {{ __('general.news') }}
                <span class="text-secondary-light text-md ms-1">
                    {{ $newsItems->count() }}
                </span>
            </h2>
            <div class="text-red-500 w-4 h-4 text-sm">
                <img src="{{ asset('assets/icons/news.svg') }}" alt="Notes icon">
            </div>

        </div>
    </div>

    <div class="flex-1 overflow-y-auto px-6 py-4 space-y-5 scrollbar-thin scrollbar-thumb-[var(--color-secondary-medium)] scrollbar-track-[var(--color-neutral-100)] hover:scrollbar-thumb-[var(--color-secondary-medium)]">
        @forelse($newsItems as $news)
            <div class="border-b border-b-secondary-light pb-3 last:border-b-0 last:pb-0">
                <p class="text-[10px] font-light text-gray-400">
                    {{ $news->published_at?->diffForHumans() ?? 'Just Now' }}
                </p>
                <p class="text-sm font-bold text-[#58595B]">
                    {{ $news->title }}
                </p>
                <p class="text-xs font-light text-secondary-medium mt-1 line-clamp-2">
                    {{ $news->description ?? 'Since 2013 (officially established in 2018), Christian Hope Center has been dedicated to supporting Christian...' }}
                </p>
                <button type="button"
                    x-on:click="selected = {
                        title: @js($news->title),
                        description: @js($news->description),
                        image: @js($news->getFirstMediaUrl('news-image') ?: null),
                    }; open = true"
                    class="ms-auto mt-2 flex text-xs font-semibold text-primary underline">
                    @lang('general.read-more') →
                </button>
            </div>
        @empty
            <div class="flex flex-col items-center justify-center  text-sm font-light text-secondary-light h-full">
                <span>@lang('general.no-available-data')</span>
            </div>
        @endforelse
    </div>

    <template x-teleport="body">
        <div x-cloak
            x-show="open"
            x-transition.opacity.duration.200ms
            class="fixed inset-0 z-50 flex items-center justify-center bg-accent/55 p-4 font-sans backdrop-blur-sm sm:p-6"
            role="dialog"
            aria-modal="true"
            x-on:click.self="open = false">
            <div x-show="open"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="translate-y-4 scale-95 opacity-0"
                x-transition:enter-end="translate-y-0 scale-100 opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="translate-y-0 scale-100 opacity-100"
                x-transition:leave-end="translate-y-4 scale-95 opacity-0"
                class="relative max-h-[90vh] w-full max-w-3xl overflow-hidden rounded-3xl border border-neutral-200 bg-white shadow-2xl">
                <button type="button"
                    x-on:click="open = false"
                    class="absolute end-4 top-4 z-10 flex size-9 items-center justify-center rounded-full border border-neutral-200 bg-white/95 text-secondary-medium shadow-sm transition hover:border-secondary-light hover:text-primary"
                    aria-label="{{ __('general.cancel') }}">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6 6 18" />
                    </svg>
                </button>

                <div class="max-h-[90vh] overflow-y-auto">
                    <template x-if="selected?.image">
                        <div class="aspect-[16/7] w-full overflow-hidden bg-neutral-100">
                            <img :src="selected.image" :alt="selected.title || ''" class="h-full w-full object-cover">
                        </div>
                    </template>

                    <div class="space-y-4 p-6 sm:p-8">
                        <p class="text-xs font-semibold uppercase tracking-wide text-primary">@lang('general.news')</p>
                        <h3 class="text-2xl font-extrabold leading-normal text-secondary-dark sm:text-3xl" x-text="selected?.title"></h3>
                        <div class="h-px bg-neutral-200"></div>
                        <p class="whitespace-pre-line text-sm font-normal leading-7 text-secondary-medium sm:text-base sm:leading-8"
                            x-text="selected?.description || ''"></p>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
