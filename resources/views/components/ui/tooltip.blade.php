@props(['content', 'position' => 'top'])

{{-- One tooltip. Flux's ui-tooltip opens on pointer hover and on keyboard focus, places itself in the
     top layer, and the open/close motion (and its reduced-motion opt-out) lives in flux-overrides.css.
     Top and bottom centre on the trigger, so placement does not flip in RTL. --}}
<flux:tooltip :content="$content" :position="$position" class="inline-flex" {{ $attributes }}>
    {{ $slot }}
</flux:tooltip>
