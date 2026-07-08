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
            @php
                $targetType = $news->newsable_type ? strtolower(class_basename($news->newsable_type)) : 'all';
            @endphp
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
                        eventDate: @js($news->event_date?->translatedFormat('d M Y')),
                        target: @js($news->newsable_name),
                        targetType: @js($targetType),
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
                        <div class="flex flex-wrap items-center gap-2">
                            <template x-if="selected?.eventDate">
                                <span class="inline-flex items-center gap-1.5 rounded-full border border-neutral-200 bg-neutral-50 px-3 py-1 text-xs font-semibold text-secondary-medium">
                                    <svg class="size-3.5 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 2v4m8-4v4M3 10h18M5 4h14a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" />
                                    </svg>
                                    <span x-text="selected.eventDate"></span>
                                </span>
                            </template>

                            <span class="inline-flex items-center gap-1.5 rounded-full border border-primary/15 bg-primary/5 px-3 py-1 text-xs font-semibold text-primary">
                                <svg x-show="selected?.targetType === 'all'" class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20a5 5 0 0 0-10 0M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm10 8a4.5 4.5 0 0 0-6-4.24M2 20a4.5 4.5 0 0 1 6-4.24" />
                                </svg>
                                <svg x-show="selected?.targetType === 'employee'" class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-7 9a7 7 0 0 1 14 0" />
                                </svg>
                                <svg x-show="selected?.targetType === 'center'" class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-7h6v7M9 10h.01M15 10h.01" />
                                </svg>
                                <svg x-show="selected?.targetType === 'program'" class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m12 3 8 4-8 4-8-4 8-4Zm-8 8 8 4 8-4M4 15l8 4 8-4" />
                                </svg>
                                <svg x-show="selected?.targetType === 'city'" class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s7-5.25 7-11a7 7 0 1 0-14 0c0 5.75 7 11 7 11Z" />
                                    <circle cx="12" cy="10" r="2.5" />
                                </svg>
                                <span x-text="selected?.target || @js(__('general.all_org'))"></span>
                            </span>
                        </div>
                        <div class="h-px bg-neutral-200"></div>
                        <p class="whitespace-pre-line text-sm font-normal leading-7 text-secondary-medium sm:text-base sm:leading-8"
                            x-text="selected?.description || ''"></p>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
