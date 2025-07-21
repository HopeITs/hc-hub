<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl" data-layout="modern">
<head>
    @include('partials.head')
</head>
<body class="antialiased bg-light text-default  layout-modern">
<x-layouts.app.page-loader/>
<div class="main-wrapper">
    <x-layouts.app.header/>
    <x-layouts.app.sidebar :title="$title ?? null"/>

    <div class="page-wrapper relative pt-[60px] mr-[252px]">
        {{ $slot }}
        <x-layouts.app.footer/>
    </div>

</div>
@include('partials.scripts')
@fluxScripts
</body>
</html>
