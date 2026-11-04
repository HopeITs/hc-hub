@props(['project' => null, 'hostName' => null])

{{-- "Hosted by {center}" on a project that has no center in its city and is
     followed up by another HR center (D-55, W-01). Text with an icon, never
     colour alone. Pass the project (its host is read from the in-memory HR
     lookup, so a list of hosted projects costs no query per row; when HR
     cannot be read the id is shown), or an already resolved `host-name` for
     rows that are plain arrays. Renders nothing for a project with no host. --}}
@php
    $hostCenterLabel = $hostName;

    if ($hostCenterLabel === null && $project !== null && $project->host_center_id !== null) {
        try {
            $hostCenterLabel = $project->hostCenter()?->name;
        } catch (\Illuminate\Database\QueryException) {
            $hostCenterLabel = null;
        }

        $hostCenterLabel ??= '#'.$project->host_center_id.' · '.__('common.hr_unavailable');
    }
@endphp

@if ($hostCenterLabel !== null)
    <span {{ $attributes->class('inline-flex items-center gap-1 rounded-full bg-secondary-soft px-2 py-0.5 text-[11px] font-bold whitespace-nowrap text-secondary-dark dark:bg-surface-2 dark:text-ink') }} data-test="hosted-by-badge">
        <flux:icon name="building-office-2" variant="micro" class="size-3 shrink-0" aria-hidden="true" />
        <span dir="auto">{{ __('projects.hosted_by', ['center' => $hostCenterLabel]) }}</span>
    </span>
@endif
