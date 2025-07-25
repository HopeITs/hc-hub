<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl" data-layout="rtl">
<head>
    @include('partials.head')
</head>
<body class="antialiased font-lato bg-light text-default relative  ">
<x-layouts.app.page-loader/>
<div class="main-wrapper">
    <livewire:layouts.header/>
    <livewire:layouts.sidebar/>
    <div class="page-wrapper relative pt-[60px] mr-[252px]">
        <div class="content p-6 pb-0">
            {{ $slot }}
        </div>



    </div>

</div>
<livewire:layouts.footer/>




@include('partials.scripts')

</body>
</html>
