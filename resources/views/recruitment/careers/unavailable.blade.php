@extends('layouts.public-recruitment')

@section('title', __('careers.unavailable_title'))

@section('content')
<main class="flex min-h-screen items-center justify-center px-4 py-10">
    <section class="w-full max-w-xl rounded-card border border-neutral-200 bg-white p-8 text-center shadow-e2">
        <x-recruitment.public-brand-logo />
        <h1 class="mt-6 text-title font-bold text-neutral-900">{{ __('careers.unavailable_title') }}</h1>
        <p class="mt-3 text-body leading-7 text-neutral-600">{{ __('careers.unavailable_message') }}</p>
    </section>
</main>
@endsection
