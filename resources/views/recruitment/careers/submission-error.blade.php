@extends('layouts.public-recruitment')

@section('title', __('careers.submission_error_title'))
@section('meta_description', __('careers.generic_submission_error'))
@section('robots', 'noindex, nofollow')

@section('content')
<main dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}" class="mx-auto flex min-h-screen w-full max-w-3xl items-center px-4 py-10 sm:px-6">
    <section class="w-full rounded-[16px] border border-neutral-200 bg-white p-8 text-center shadow-e1 sm:p-12">
        <x-recruitment.public-brand-banner />
        <h1 class="mt-8 text-title font-bold text-neutral-900">{{ __('careers.submission_error_title') }}</h1>
        <p class="mx-auto mt-3 max-w-xl text-body leading-7 text-neutral-600">{{ $submissionError ?? __('careers.generic_submission_error') }}</p>
    </section>
</main>
@endsection
