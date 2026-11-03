{{--
    A draft card's state chip: icon + words + fill shape, never colour (DESIGN.md).

      <x-assistant.status tone="attn" icon="pen" :label="__('…')" />

    tone: done (surface) | attn (ink outline) | stop (dashed) | wait (card + line)

    Stand-in for the shared <x-status> of hc-hr's visual identity, until that
    component is in the hub; then the assistant views switch to <x-status>
    and this file goes.
--}}
@props(['tone' => 'wait', 'icon' => 'dot', 'label' => null])

<span {{ $attributes->class([
    'inline-flex max-w-full items-center gap-1.5 rounded-full px-2.5 py-0.5 text-caption font-semibold text-ink',
    'bg-surface' => $tone === 'done',
    'border border-ink' => $tone === 'attn',
    'border border-dashed border-ink-3' => $tone === 'stop',
    'border border-line-soft bg-card' => ! in_array($tone, ['done', 'attn', 'stop'], true),
]) }} data-tone="{{ $tone }}"><x-assistant.ic :name="$icon" /><span class="min-w-0 truncate">{{ $label ?? $slot }}</span></span>
