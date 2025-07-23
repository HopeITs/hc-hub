<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl" data-layout="rtl">
<head>
    @include('partials.head')
</head>
<body class="antialiased font-lato bg-light text-default  layout-rtl">
<x-layouts.app.page-loader/>
<div class="main-wrapper">
    <livewire:layouts.header/>
    <livewire:layouts.sidebar/>
    <div class="page-wrapper relative pt-[60px] mr-[252px]">
        {{ $slot }}

    </div>
    <livewire:layouts.footer/>
</div>
@include('partials.scripts')
@fluxScripts
</body>
</html>
