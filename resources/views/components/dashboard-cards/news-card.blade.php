@php
    use App\Models\News;
    $newsItems = News::with('newsable')
        ->latest()
        ->get()
        ->filter(fn($n) => !$n->is_expired);
@endphp

<div class="relative w-full h-full max-w-md py-6 bg-white rounded-3xl shadow-sm flex flex-col overflow-hidden">
    <div class="flex items-center justify-between px-6 pt-5 pb-1 flex-shrink-0">
        <div class="flex items-center gap-2">
            <h2 class="text-lg font-base text-[#58595B]">
                {{ __('News') ?? 'News' }}
                <span class="text-secondary-light text-md ml-1">
                    {{ $newsItems->count() }}
                </span>
            </h2>
            <svg xmlns="http://www.w3.org/2000/svg"
                 fill="currentColor"
                 viewBox="0 0 24 24"
                 class="w-6 h-6 text-primary">
                <path fill-rule="evenodd" d="M5.25 3A2.25 2.25 0 003 5.25v13.5A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V5.25A2.25 2.25 0 0018.75 3H5.25zM6 6h12v1.5H6V6zm0 3h12v1.5H6V9zm0 3h7.5v1.5H6V12zm0 3h7.5v1.5H6V15z" clip-rule="evenodd"/>
            </svg>

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
                    Read More →
                </a>
            </div>
        @empty
            <div class="flex flex-col items-center justify-center text-[var(--color-neutral-500)] text-sm py-40">
                <div class="text-4xl mb-2">🗞️</div>
                <p class="font-medium">No recent company updates</p>
            </div>
        @endforelse
    </div>
</div>
