@php
    use App\Models\News;
    $newsItems = News::latest()->get()->filter(fn($n) => !$n->is_expired);
@endphp

<div class="relative w-full max-w-sm h-[340px] bg-[var(--color-neutral-50)] rounded-3xl shadow-lg ring-1 ring-[var(--color-primary-400)] overflow-hidden">
    <div class="flex items-center gap-2 px-5 pt-4 pb-2">
        <div class="flex items-center justify-center h-8 w-8 rounded-lg bg-[var(--color-primary-500)] text-white text-lg shadow-sm">📰</div>
        <h2 class="text-lg font-extrabold text-[var(--color-primary)] tracking-tight">Company News</h2>
    </div>

    <hr class="border-[var(--color-primary-200)] mx-5">

    <div class="relative p-5 space-y-4 overflow-y-auto scroll-smooth max-h-[270px] scrollbar-thin scrollbar-thumb-[var(--color-primary-300)] scrollbar-track-[var(--color-neutral-100)] hover:scrollbar-thumb-[var(--color-primary-400)]">
        @forelse($newsItems as $news)
            <div class="flex items-start gap-4 p-4 bg-white rounded-2xl border border-[var(--color-primary-100)] hover:border-[var(--color-primary-300)] hover:shadow transition">
                @if($news->photo)
                    <img src="{{ Storage::url($news->photo) }}" class="h-12 w-12 rounded-full object-cover">
                @else
                    <div class="flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 text-blue-600 text-xl">📰</div>
                @endif
                <div class="flex-1">
                    <p class="text-sm font-bold text-[var(--color-neutral-800)] leading-tight">{{ $news->title }}</p>
                    @if($news->description)
                        <p class="text-xs text-[var(--color-neutral-600)] mt-1 line-clamp-2">{{ $news->description }}</p>
                    @endif
                    <p class="text-xs text-[var(--color-neutral-500)] mt-0.5">{{ $news->published_at?->diffForHumans() }}</p>
                </div>
            </div>
        @empty
            <div class="flex flex-col items-center justify-center text-[var(--color-neutral-500)] text-sm py-10">
                <div class="text-4xl mb-2">🗞️</div>
                <p class="font-medium">No recent company updates</p>
            </div>
        @endforelse
    </div>
</div>
