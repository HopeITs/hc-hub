<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl" >
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white antialiased font-lato">

        <div class="bg-background flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">

            <div class="flex w-full max-w-sm flex-col gap-2">
                <a href="{{ route('home') }}" class="flex flex-col items-center gap-2 font-medium" wire:navigate>
                    <span class="flex h-9 w-9 mb-1 items-center justify-center rounded-md">
{{--                        <x-app-logo-icon class="size-9 fill-current text-black " />--}}
                        <x-app-logo/>
                    </span>
                    <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
                </a>
                <div class="max-w-sm p-6 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700">
                <div class="flex flex-col gap-6">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>
        @fluxScripts
    </body>
</html>
