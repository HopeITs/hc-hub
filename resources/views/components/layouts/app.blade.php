<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-white  font-lato">
<x-layouts.app.header/>
<livewire:layout.sidebar/>
<div class="p-4 pt-8  sm:mr-60">
    <div class="p-4 border-1 border-gray-200 border-dashed rounded-lg dark:border-gray-700 mt-14">
        {{$slot}}
    </div>
</div>
   @include('partials.scripts')
</body>
</html>
