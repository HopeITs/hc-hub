@extends('layouts.public-recruitment')

@section('title', __('careers.submitted_title'))
@section('meta_description', __('careers.submitted_message'))
@section('robots', 'noindex, nofollow')

@section('content')
<main dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}" class="mx-auto flex min-h-screen w-full max-w-3xl items-center px-4 py-10 sm:px-6">
    <section class="w-full rounded-[16px] border border-neutral-200 bg-white p-8 text-center shadow-e1 sm:p-12">
        <x-recruitment.public-brand-logo />
        <div class="mx-auto mt-8 flex size-14 items-center justify-center rounded-full bg-primary text-white" aria-hidden="true">
            <svg class="size-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6" />
            </svg>
        </div>
        <h1 class="mt-5 text-title font-bold text-neutral-900">{{ __('careers.submitted_title') }}</h1>
        <p class="mx-auto mt-3 max-w-xl text-body leading-7 text-neutral-600">{{ __('careers.submitted_message') }}</p>
    </section>
</main>
@endsection
