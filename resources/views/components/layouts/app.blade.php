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

<div id="page-gif-overlay" class="fixed inset-0 flex items-center justify-center bg-white bg-opacity-90 z-[9999]">
    <video autoplay loop muted playsinline class="w-74 h-74">
        <source src="{{ asset('assets/GIF/hr-website-1.mp4') }}" type="video/mp4">
    </video>
</div>
<livewire:layout.sidebar/>


<div class="flex flex-col min-h-screen sm:ms-60">



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
    if (typeof window.preloaderScriptLoaded === 'undefined') {
        window.preloaderScriptLoaded = true;

        function hidePreloader() {
            const overlay = document.getElementById('page-gif-overlay');
            if (!overlay) return;

            overlay.style.transition = "opacity 0.6s ease";
            overlay.style.opacity = "0";

            setTimeout(() => {
                overlay.style.display = "none";
            }, 200);
        }

        const MIN_TIME = 2500;
        const start = performance.now();

        function scheduleHide() {
            const elapsed = performance.now() - start;
            const remaining = MIN_TIME - elapsed;

            setTimeout(hidePreloader, remaining > 0 ? remaining : 0);
        }

        document.addEventListener("DOMContentLoaded", scheduleHide);
        document.addEventListener("livewire:load", scheduleHide);

        setTimeout(hidePreloader, MIN_TIME + 1000);
    }
</script>



</body>

</html>
