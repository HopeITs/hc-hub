@props(['tone' => 'neutral', 'size' => 'md', 'label' => null])

@php
    // One button (docs/redesign-plan/02-shared-components.md): tones primary / neutral / danger / ghost,
    // sizes sm / md / lg and icon (a square icon-only button). Sizes and the danger look live in
    // resources/css/button.css, keyed on the data-size / data-tone hooks below, so no page needs `size-8!`
    // or `text-status-critical!`. 'secondary' (the old default) and 'xs' are kept as aliases.
    $tone = $tone === 'secondary' ? 'neutral' : $tone;
    $size = $size === 'xs' ? 'sm' : $size;

    $toneClasses = match ($tone) {
        'primary' => 'border-primary! bg-primary! text-white! hover:border-primary! hover:bg-primary! dark:border-brand! dark:bg-brand! dark:hover:border-brand-hover! dark:hover:bg-brand-hover!',
        'neutral' => 'border-secondary-light! bg-white! text-secondary-dark! hover:bg-secondary-soft! dark:border-line-strong! dark:bg-surface-2! dark:text-ink! dark:hover:bg-surface-3!',
        'ghost' => 'border-0! bg-transparent! text-secondary-dark! hover:bg-secondary-soft! dark:text-ink! dark:hover:bg-white/[0.06]!',
        // Quiet destructive: critical text on the page, a soft critical wash on hover (see button.css).
        'danger' => '',
        // The solid dark button (was tone="neutral" before the tones were unified).
        'inverse' => 'border-secondary-dark! bg-secondary-dark! text-white! hover:border-secondary-medium! hover:bg-secondary-medium! dark:border-ink! dark:bg-ink! dark:text-surface-0! dark:hover:bg-ink/85!',
        // Page header actions (Export, Filters, Cancel): the look comes from .page-action in flux-overrides.css.
        'action' => 'page-action',
        default => throw new InvalidArgumentException("Unknown x-ui.button tone [{$tone}]."),
    };

    if (! in_array($size, ['sm', 'md', 'lg', 'icon'], true)) {
        throw new InvalidArgumentException("Unknown x-ui.button size [{$size}].");
    }

    $ariaLabel = $label ?? $attributes->get('aria-label');

    if ($size === 'icon' && ($ariaLabel === null || $ariaLabel === '')) {
        throw new InvalidArgumentException('An icon-only x-ui.button needs a label or aria-label.');
    }

    $fluxVariant = $tone === 'primary' ? 'primary' : 'filled';
    $fluxSize = $size === 'sm' ? 'sm' : null;
@endphp

<flux:button :variant="$fluxVariant" :size="$fluxSize" data-ui-button :data-tone="$tone" :data-size="$size" :aria-label="$ariaLabel" {{ $attributes->except('aria-label')->class($toneClasses) }}>
    {{ $slot }}
</flux:button>
