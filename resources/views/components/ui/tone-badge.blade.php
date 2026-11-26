@props(['tone' => 'neutral', 'icon' => null])

{{-- Old name kept for its callers (plan task 2.4). A badge with an icon is a
     status, so it renders as x-ui.chip; without one it is a word that is not
     a status (a count, a group, a tag) and renders as x-ui.tag. The tone is a
     StatusTone or its value (good / warn / critical / active / neutral). --}}
@if ($icon !== null)
    <x-ui.chip :tone="$tone" :icon="$icon" :attributes="$attributes">{{ $slot }}</x-ui.chip>
@else
    <x-ui.tag :tone="$tone" :attributes="$attributes">{{ $slot }}</x-ui.tag>
@endif
