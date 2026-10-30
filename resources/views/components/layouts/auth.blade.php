@props(['title' => null, 'description' => null])

@php
    use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

    $locale = app()->getLocale();
    $switchTo = $locale === 'ar' ? 'en' : 'ar';
@endphp

{{-- Sign-in pages (Fortify, task 1.1): media-request's centred card on the soft
     page colour. Semantic tokens only, so dark mode needs no extra classes. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}" dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    @include('partials.head', ['title' => $title, 'description' => $description ?? __('auth.meta-description')])
    <style nonce="{{ Vite::cspNonce() }}">[x-cloak] { display: none !important; }</style>
</head>
<body class="min-h-dvh bg-soft font-SomarSans text-ink antialiased">
    <div class="flex min-h-dvh flex-col">
        <div class="flex justify-end gap-1 px-4 pt-4">
            <a href="{{ LaravelLocalization::getLocalizedURL($switchTo, null, [], true) }}"
               hreflang="{{ $switchTo }}" lang="{{ $switchTo }}"
               data-test="auth-language-switch"
               class="inline-grid min-h-11 min-w-11 place-items-center rounded-control px-3 text-body font-semibold text-ink-2 transition hover:bg-hover hover:text-ink">
                {{ $switchTo === 'ar' ? 'العربية' : 'English' }}
            </a>
            <x-theme-toggle />
        </div>

        <main id="main-content" class="flex flex-1 items-center justify-center px-4 py-8">
            <div class="flex w-full max-w-[26rem] flex-col gap-6">
                <x-app-logo class="mx-auto h-auto w-[207px]" />

                <div class="rounded-surface border border-line-soft bg-card p-6 shadow-e1 sm:p-8">
                    {{ $slot }}
                </div>
            </div>
        </main>

        <footer class="px-4 pb-4 text-center text-body text-ink-2">
            &copy; {{ date('Y') }} - {{ __('auth.copyright') }}
        </footer>
    </div>

    @include('partials.scripts')
</body>
</html>
