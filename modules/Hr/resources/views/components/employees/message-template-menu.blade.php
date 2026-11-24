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
    $announcements = collect();

    if ($viewer?->can('use', \Modules\Hr\Models\MessageTemplate::class)) {
        // The ready-made announcements about one person: the composer opens with the person's Arabic name already in.
        $announcements = collect(['welcome', 'volunteer_resignation', 'administrative_change'])
            ->map(fn (string $key) => \Modules\Hr\Support\Announcements\AnnouncementTemplates::find($key))
            ->filter()
            ->values();

        $renderer = app(\Modules\Hr\Support\Employees\MessageTemplateRenderer::class);

        $items = \Modules\Hr\Models\MessageTemplate::query()->active()->orderBy('channel')->orderBy('name')->get()
            ->map(fn (\Modules\Hr\Models\MessageTemplate $template): array => [
                'template' => $template,
                'url' => $renderer->url($template, $employee, $viewer),
            ]);
    }
@endphp

@if ($items->isNotEmpty() || $announcements->isNotEmpty())
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
            @foreach ($announcements as $announcement)
                @php($compose = \Illuminate\Support\Js::from(['template' => $announcement->key(), 'prefill' => ['name' => $employee->nameForLocale('ar')]]))
                <flux:menu.item x-on:click="Livewire.dispatch('announcement-compose', {{ $compose }})" data-announcement-link="{{ $announcement->key() }}">
                    <span>{{ $announcement->label() }}</span>
                    <span class="ms-2 text-xs text-secondary-medium dark:text-ink-muted">{{ __('hr_messages.announcements.short') }}</span>
                </flux:menu.item>
            @endforeach
        </flux:menu>
    </flux:dropdown>

    @once
        <livewire:hr::message-templates.announcements />
    @endonce
@endif
