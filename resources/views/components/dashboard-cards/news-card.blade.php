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



<div class="relative w-full h-full bg-white rounded-surface shadow-e1 flex flex-col overflow-hidden">
    <div class="flex items-center justify-between px-6 pt-4 pb-4 flex-shrink-0">
        <div class="flex items-center gap-3">
            <h2 class="text-md font-base text-[#58595B]">
                {{ __('general.news') }}
                <span class="text-secondary-light text-md ms-1">
                    {{ $newsItems->count() }}
                </span>
            </h2>
            <div class="text-red-500 w-4 h-4 text-body">
                <img src="{{ asset('assets/icons/news.svg') }}" alt="Notes icon">
            </div>

        </div>
    </div>

    <div class="flex-1 overflow-y-auto px-6 py-4 space-y-4 scrollbar-thin scrollbar-thumb-[var(--color-secondary-medium)] scrollbar-track-[var(--color-neutral-100)] hover:scrollbar-thumb-[var(--color-secondary-medium)]">
        @forelse($newsItems as $news)
            <div class="border-b border-b-secondary-light pb-3 last:border-b-0 last:pb-0">
                <p class="text-caption font-light text-secondary-light">
                    {{ $news->published_at?->diffForHumans() ?? 'Just Now' }}
                </p>
                <p class="text-body font-bold text-[#58595B]">
                    {{ $news->title }}
                </p>
                <p class="text-small font-light text-secondary-medium mt-1 line-clamp-2">
                    {{ $news->description ?? 'Since 2013 (officially established in 2018), Christian Hope Center has been dedicated to supporting Christian...' }}
                </p>
                <a href="{{ route('news.index', $news->id) }}" class="text-small flex justify-end font-semibold text-primary mt-2   underline">
                    @lang('general.read-more') →
                </a>
            </div>
        @empty
            <div class="flex flex-col items-center justify-center  text-body font-light text-secondary-light h-full">
                <span>@lang('general.no-available-data')</span>
            </div>
        @endforelse
    </div>
</div>
