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
    $team = app(\Modules\Media\Support\MediaTeam::class);
    $isMediaTeam = $team->isMember($user->profile) || $team->canActAsMediaManager($user);
    $managesPrograms = $team->managedProgramIds($user->profile) !== [] || $user->myRole() === 'super-admin';
    $nav = $team->isRequesterOnly($user) ? [] : array_filter([
        ['media-requests.home', 'nav.home', 'home', $user->hasActivePermission('media.requests.view-own'), ['media-requests.home']],
        ['media-requests.index', 'nav.my', 'inbox', $user->hasActivePermission('media.requests.view-own'), ['media-requests.index', 'media-requests.show']],
        ['media-requests.approvals', 'nav.approvals', 'shield', $managesPrograms && $user->hasActivePermission('media.requests.approve'), ['media-requests.approvals']],
        ['media-requests.board', 'nav.board', 'board', $isMediaTeam && $user->hasActivePermission('media.requests.work'), ['media-requests.board']],
        ['media-requests.drive', 'nav.drive', 'layers', $user->hasActivePermission('media.requests.view-own'), ['media-requests.drive']],
        ['media-requests.stats', 'nav.stats', 'chart', $isMediaTeam && $user->hasActivePermission('media.requests.work'), ['media-requests.stats']],
        ['media-requests.pack', 'nav.pack', 'box', $user->hasActivePermission('media.requests.create'), ['media-requests.pack']],
        ['media-requests.log', 'nav.log', 'list', $isMediaTeam && $user->hasActivePermission('media.requests.view-log'), ['media-requests.log']],
        ['media-requests.assistant', 'nav.assistant', 'spark', $isMediaTeam && $user->hasActivePermission('media.requests.view-log'), ['media-requests.assistant']],
    ], fn ($item) => $item[3]);
@endphp
<div {{ $attributes->class(['mr']) }}
     x-data x-init="document.documentElement.dataset.gotIt = @js(__('media.hint.gotIt')); document.documentElement.dataset.back = @js(__('media.d.back'))">
    @if (! $bare && $nav !== [])
        <nav class="mr-nav" aria-label="{{ __('media.app') }}">
            @foreach ($nav as [$route, $label, $icon, , $active])
                <a href="{{ route($route) }}" wire:navigate @if (request()->routeIs(...$active)) aria-current="page" @endif>
                    <x-ic :n="$icon" />{{ __('media.'.$label) }}
                </a>
            @endforeach
            @if ($user->hasActivePermission('media.requests.create'))
                <a href="{{ app(\Modules\Media\Support\HcGenerator::class)->url() }}" target="_blank" rel="noopener" lang="en">
                    <x-ic n="wand" />HC Generator
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
