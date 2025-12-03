<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">

<head>
    @include('partials.head')

    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>

    <title>@yield('title', 'Hope HR')</title>
</head>

<body class="antialiased min-h-screen bg-white">
<div id="page-gif-overlay"
     class="fixed inset-0 flex items-center justify-center bg-white bg-opacity-90 z-[9999]">

    <video autoplay loop muted playsinline class="w-74 h-74">
        <source src="{{ asset('assets/GIF/hr-website.mp4') }}" type="video/mp4">
    </video>

</div>
<livewire:layout.sidebar/>

<div class="flex flex-col min-h-screen sm:ms-60">

    <header class="fixed top-0 w-full bg-gray-100 sm:ms-60 z-10">
        <div class="px-4 py-3 flex justify-end sm:hidden">
            <button id="sidebar-toggle" type="button"
                    class="inline-flex items-center p-2 text-sm text-gray-500 rounded-lg sm:hidden hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-300">
                <span class="sr-only">Open sidebar</span>
                <svg class="w-6 h-6" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20"
                     xmlns="http://www.w3.org/2000/svg">
                    <path clip-rule="evenodd" fill-rule="evenodd"
                          d="M2 4.75A.75.75 0 012.75 4h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 4.75zm0 10.5a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5a.75.75 0 01-.75-.75zM2 10a.75.75 0 01.75-.75h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 10z">
                    </path>
                </svg>
            </button>
            <livewire:notifications.dropdown/>
        </div>
    </header>

    <livewire:layout.header/>

    <main class="{{ View::hasSection('greeting') ? '' : 'pt-[120px] ' }} bg-gray-100 flex-grow flex flex-col items-center">


        @if (session('status'))
            <div class="alert alert-success">
                {{ session('status') }}
            </div>
        @endif
        <div class="px-4 pb-6 w-11/12  min-h-full">
            @yield('greeting')
            {{ $slot }}
        </div>
    </main>

    <footer class="bg-gray-100">
        <x-layouts.app.footer/>
    </footer>
</div>

<div id="sidebar-overlay" class="fixed inset-0 bg-gray-200 opacity-50 z-30 sm:hidden hidden"></div>


@include('partials.scripts')

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const sidebar = document.getElementById('cta-button-sidebar');
        const toggleButton = document.getElementById('sidebar-toggle');
        const overlay = document.getElementById('sidebar-overlay');

        const toggleSidebar = () => {
            sidebar?.classList.toggle('translate-x-full');
            overlay?.classList.toggle('hidden');
        };

        if (toggleButton && sidebar && overlay) {
            toggleButton.addEventListener('click', toggleSidebar);
            overlay.addEventListener('click', toggleSidebar);
        }
    });
</script>
@stack('scripts')
<script>
    window.addEventListener('load', () => {
        const overlay = document.getElementById('page-gif-overlay');
        if (overlay) {
            setTimeout(() => {
                overlay.style.opacity = '0';
                overlay.style.transition = 'opacity 0.6s ease';
                setTimeout(() => overlay.style.display = 'none', 300);
            }, 4000); // <-- duration before hiding (in milliseconds)
        }
    });
</script>
</body>

</html>
