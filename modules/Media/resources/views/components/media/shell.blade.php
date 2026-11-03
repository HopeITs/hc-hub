{{--
    Every media page renders inside this, in the hub layout: the .mr wrapper that
    media-request's stylesheet is scoped to (modules/Media/resources/css/media.css),
    the module's own page links (what media-request had in its top bar; the hub's
    sidebar, header, bell, language and theme switches replace the rest), and,
    once per page, the request form and the proofreader they open.

    bare: a page shown inside another media page (a request opened on the home
    screen) — the wrapper only, no links and no second form.
--}}
@props(['bare' => false])
@php
    $user = auth()->user();
    // A requester has the home screen only, so the bar is not drawn for them.
    $nav = app(\Modules\Media\Support\MediaTeam::class)->isRequesterOnly($user)
        ? []
        : app(\Modules\Media\Support\MediaNavigation::class)->pagesFor($user);
@endphp
<div {{ $attributes->class(['mr']) }}
     x-data x-init="document.documentElement.dataset.gotIt = @js(__('media.hint.gotIt')); document.documentElement.dataset.back = @js(__('media.d.back'))">
    @if (! $bare && $nav !== [])
        <nav class="mr-nav" aria-label="{{ __('media.app') }}">
            @foreach ($nav as $page)
                <a href="{{ route($page['route']) }}" wire:navigate @if (request()->routeIs(...$page['active'])) aria-current="page" @endif>
                    <x-media.ic :n="$page['icon']" />{{ __('media.nav.'.$page['key']) }}
                </a>
            @endforeach
            @if ($user->hasActivePermission('media.requests.create'))
                <a href="{{ app(\Modules\Media\Support\HcGenerator::class)->url() }}" target="_blank" rel="noopener" lang="en">
                    <x-media.ic n="wand" />HC Generator
                </a>
            @endif
        </nav>
    @endif

    <div class="mr-page">
        {{ $slot }}
    </div>

    @unless ($bare)
        @if ($user->hasActivePermission('media.requests.create'))
            <livewire:media-requests.request-wizard />
        @endif
        <livewire:media-requests.proofreader-panel />
    @endunless
</div>
