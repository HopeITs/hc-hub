@props(['campaign', 'title', 'versionNumber', 'versionStatus', 'publishedVersionNumber' => null, 'next' => null])

{{-- The header shared by the job-post and application-form editors: which campaign, which version, and what to do next after publishing. --}}
@php
    $muted = 'text-secondary-medium dark:text-ink-muted';
    $request = $campaign->vacancy?->createdFromRequest;
@endphp

<x-ui.detail-header
    :back="\App\Support\BackUrl::for(route('hr.recruitment.campaigns.show', ['campaignId' => $campaign->id]))"
    :breadcrumbs="[['label' => __('hr_recruitment.hub.section')], ['label' => __('hr_recruitment.hub.titles.campaigns'), 'href' => route('hr.recruitment.campaigns.index')], ['label' => $campaign->vacancy?->position?->title ?? '#'.$campaign->id, 'href' => route('hr.recruitment.campaigns.show', ['campaignId' => $campaign->id])]]"
    :title="$title"
>
    <x-slot:meta>
        <span>{{ __('hr_recruitment.job_post.version') }}: <span class="tabular-nums">{{ $versionNumber }}</span></span>
        <x-ui.enum-badge :case="\App\Enums\StatusTone::Neutral" :label="__('hr_recruitment.job_post.status_'.$versionStatus)" :tone="$versionStatus === 'published' ? \App\Enums\StatusTone::Good : \App\Enums\StatusTone::Warn" />
        <span>{{ __('hr_recruitment.job_post.published_version') }}: <span class="tabular-nums">{{ $publishedVersionNumber ?? '—' }}</span></span>
        @if ($request)
            <span>{{ __('hr_recruitment.job_post.approved_request') }}: <bdi class="tabular-nums">{{ $request->request_number }}</bdi></span>
        @endif
    </x-slot:meta>
</x-ui.detail-header>

@if ($next)
    <x-ui.panel :clip="false" class="flex flex-wrap items-center justify-between gap-3 border-s-4 border-s-primary p-4 dark:border-s-brand" role="status">
        <p class="text-sm">{{ $next['message'] }}</p>
        <x-ui.button tone="primary" :href="$next['url']" wire:navigate>{{ $next['label'] }}</x-ui.button>
    </x-ui.panel>
@endif
