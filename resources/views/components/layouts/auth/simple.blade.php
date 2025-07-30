<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
<head>
    @include('partials.head')

</head>
<body class="min-h-screen bg-gray-100 antialiased font-SomarSans">

<div class="bg-background flex min-h-svh flex-col items-center justify-between gap-6  pt-6 md:pt-36">

    <div class="flex w-full max-w-sm flex-col gap-2">
        <a href="{{ route('home') }}" class="flex flex-col items-center gap-2 font-medium" wire:navigate>
                    <span class="flex w-48 mb-12 items-center justify-center rounded-md">
                        <x-app-logo class="lg:w-48"/>
                    </span>
            <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
        </a>
        <div
            class="max-w-sm p-6 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700">
            <div class="flex flex-col gap-6">
                {{ $slot }}
            </div>
        </div>
    </div>
    <x-layouts.app.footer/>
</div>

{{--@fluxScripts--}}
</body>
</html>
