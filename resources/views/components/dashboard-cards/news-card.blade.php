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



<div class="relative w-full h-full  py-6 bg-white rounded-3xl shadow-[0px_0px_25px_-14px_rgba(0,_0,_0,_0.30)] flex flex-col overflow-hidden">
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

    <div class="flex-1 overflow-y-auto px-6 py-4 space-y-5 scrollbar-thin scrollbar-thumb-[var(--color-primary-300)] scrollbar-track-[var(--color-neutral-100)] hover:scrollbar-thumb-[var(--color-primary-400)]">
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
                <a href="{{ route('news.index', $news->id) }}" class="text-xs flex justify-end font-semibold text-primary mt-2   underline">
                    @lang('general.read-more') →
                </a>
            </div>
        @empty
            <div class="flex flex-col items-center justify-center text-[var(--color-neutral-500)] text-sm py-40">
                <div class="text-4xl mb-2">🗞️</div>
                <p class="font-medium">@lang('general.no-news-text')</p>
            </div>
        @endforelse
    </div>
</div>
