<!DOCTYPE html>
{{-- The public careers site's own page shell: no desk, no sign-in, no Livewire. --}}
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    @php
        $publicPageTitle = trim($__env->yieldContent('title', __('hr_recruitment.careers.title')));
        $publicDescription = \Illuminate\Support\Str::limit(trim(strip_tags($__env->yieldContent('meta_description', __('hr_recruitment.careers.meta_description')))), 160);
        $publicRobots = trim($__env->yieldContent('robots', 'noindex, nofollow'));
    @endphp
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $publicDescription }}">
    <meta name="robots" content="{{ $publicRobots }}">
    <meta name="color-scheme" content="light">
    <title>{{ $publicPageTitle }} - {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('logo-tracking-icon.svg') }}" type="image/svg+xml">
    @fonts
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-neutral-50 text-neutral-900 antialiased">
    @yield('content')
    @stack('scripts')
</body>
</html>
