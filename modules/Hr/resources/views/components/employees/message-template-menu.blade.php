@props(['employee'])

{{--
    "Use a template": the active email and WhatsApp templates, each already
    filled in for this person. A link opens a new message (mailto: or
    WhatsApp) for the viewer to read and edit; nothing is sent. Shown only to
    whoever holds message-templates.use, and only when a template exists.
--}}
@php
    $viewer = auth()->user();
    $items = collect();

    if ($viewer?->can('use', \Modules\Hr\Models\MessageTemplate::class)) {
        $renderer = app(\Modules\Hr\Support\Employees\MessageTemplateRenderer::class);

        $items = \Modules\Hr\Models\MessageTemplate::query()->active()->orderBy('channel')->orderBy('name')->get()
            ->map(fn (\Modules\Hr\Models\MessageTemplate $template): array => [
                'template' => $template,
                'url' => $renderer->url($template, $employee, $viewer),
            ]);
    }
@endphp

@if ($items->isNotEmpty())
    <flux:dropdown position="bottom" align="end" data-message-template-menu>
        <x-ui.button icon="chat-bubble-left-right" icon:trailing="chevron-down">{{ __('hr_messages.use_template') }}</x-ui.button>
        <flux:menu>
            @foreach ($items as $item)
                @if ($item['url'] !== null)
                    <flux:menu.item :href="$item['url']" :target="$item['template']->isWhatsapp() ? '_blank' : null" data-message-template-link="{{ $item['template']->id }}">
                        <span dir="auto">{{ $item['template']->name }}</span>
                        <span class="ms-2 text-xs text-secondary-medium dark:text-ink-muted">{{ __('hr_messages.channels.'.$item['template']->channel) }}</span>
                    </flux:menu.item>
                @else
                    <flux:menu.item disabled>
                        <span dir="auto">{{ $item['template']->name }}</span>
                        <span class="ms-2 text-xs">{{ __('hr_messages.no_address') }}</span>
                    </flux:menu.item>
                @endif
            @endforeach
        </flux:menu>
    </flux:dropdown>
@endif
