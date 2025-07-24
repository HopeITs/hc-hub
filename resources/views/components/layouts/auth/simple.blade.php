<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" >
    <head>
        <title>
            Hope Center HR Login
        </title>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-gray-100 antialiased">
        <div class="bg-background flex min-h-svh flex-col items-center justify-center gap-6 ">
            <div class="flex w-full max-w-sm flex-col gap-2">
                <div class="flex flex-col gap-6">
                    {{ $slot }}
                </div>
            </div>
        </div>

    </body>
</html>
