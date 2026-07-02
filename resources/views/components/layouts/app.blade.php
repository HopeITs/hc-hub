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

<body class="antialiased min-h-screen bg-white"
      x-data="{ sidebarCollapsed: localStorage.getItem('sidebar-collapsed') === 'true' }"
      x-on:sidebar-collapse-toggle.window="
          sidebarCollapsed = !sidebarCollapsed;
          localStorage.setItem('sidebar-collapsed', sidebarCollapsed);
      ">

<div id="page-gif-overlay"
     style="position:fixed; inset:0; display:flex; align-items:center; justify-content:center; background:#ffffff; z-index:9999;">
    <video autoplay loop muted playsinline style="width:18.5rem; height:18.5rem; object-fit:contain;">
        <source src="{{ asset('assets/GIF/hr-website-2.mp4') }}" type="video/mp4">
    </video>
</div>
<livewire:layout.sidebar/>


<div class="flex flex-col min-h-screen transition-[margin] duration-300 md:ms-60"
     :class="sidebarCollapsed ? 'md:!ms-20' : 'md:!ms-60'">



    <livewire:layout.header/>

    <main class="{{ View::hasSection('greeting') ? '' : 'pt-[120px] ' }} bg-gray-100 flex-grow flex flex-col items-center">


        @if (session('status'))
            <div class="alert alert-success">
                {{ session('status') }}
            </div>
        @endif
        <div class="px-4 pb-6 max-lg:w-full md:w-11/12  min-h-full">
            @yield('greeting')
            {{ $slot }}
        </div>
    </main>

    <footer class="bg-gray-100">
        <x-layouts.app.footer/>
    </footer>
</div>

@include('partials.scripts')
@stack('scripts')
<script>
    function hidePreloader() {
        const overlay = document.getElementById('page-gif-overlay');
        if (!overlay) return;

        overlay.style.transition = "opacity 0.8s ease";
        overlay.style.opacity = "0";

        setTimeout(() => {
            overlay.style.display = "none";
        }, 100);
    }

    function showPreloader() {
        const overlay = document.getElementById('page-gif-overlay');
        if (!overlay) return;

        overlay.style.display = "flex";
        overlay.style.opacity = "1";
    }

    const MIN_TIME = 1500;
    let startTime = performance.now();

    function scheduleHide() {
        const elapsed = performance.now() - startTime;
        const remaining = MIN_TIME - elapsed;

        setTimeout(hidePreloader, remaining > 0 ? remaining : 0);
    }

    document.addEventListener('DOMContentLoaded', () => {
        startTime = performance.now();
        scheduleHide();
    });

    document.addEventListener('livewire:navigated', () => {
        startTime = performance.now();
        scheduleHide();
    });

    setTimeout(hidePreloader, 2000);
</script>





</body>

</html>
