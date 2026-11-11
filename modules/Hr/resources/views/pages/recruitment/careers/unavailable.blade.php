@extends('hr::components.recruitment.careers-layout')

@section('title', __('hr_recruitment.careers.unavailable_title'))

@section('content')
<main dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" class="flex min-h-screen items-center justify-center px-4 py-10 sm:px-6">
    <section class="flex min-h-[292px] w-full max-w-[540px] flex-col items-center justify-center rounded-[16px] border border-neutral-200 bg-white p-10 text-center shadow-card">
        <x-hr::recruitment.public-brand-logo />
        <h1 class="mt-7 text-title font-bold text-neutral-900">{{ __('hr_recruitment.careers.unavailable_title') }}</h1>
        <p class="mt-1 max-w-md text-base leading-5 text-neutral-600">{{ __('hr_recruitment.careers.unavailable_message') }}</p>
    </section>
</main>
@endsection
