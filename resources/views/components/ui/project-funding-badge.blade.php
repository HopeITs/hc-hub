@props(['project'])

{{-- Where a project stands on its way to funding, as an x-ui.chip (plan task
     2.4), so text and an icon, never colour alone: awaiting the owner's
     acknowledgement (warn), awaiting the donor's decision (active), or funded
     and waiting for MEAL's report template (good). Needs `proposals:id,status`
     and `ownerAssignment` loaded (fundingState() loads them when a page forgot). --}}
@php
    $fundingState = $project->fundingState();
    $fundingBadge = match ($fundingState) {
        'awaiting_owner' => ['icon' => 'clock', 'tone' => 'warn', 'label' => __('projects.awaiting_owner_acknowledgement')],
        'awaiting_funding' => ['icon' => 'banknotes', 'tone' => 'active', 'label' => __('projects.awaiting_funding')],
        'funded_awaiting_template' => ['icon' => 'document-check', 'tone' => 'good', 'label' => __('projects.funded_awaiting_template')],
        default => null,
    };
@endphp

@if ($fundingBadge !== null)
    <x-ui.chip :tone="$fundingBadge['tone']" :icon="$fundingBadge['icon']" :attributes="$attributes->merge(['data-test' => 'funding-badge-'.$fundingState])">{{ $fundingBadge['label'] }}</x-ui.chip>
@endif
