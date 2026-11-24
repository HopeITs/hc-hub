<?php

use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\Hr\Models\MessageTemplate;
use Modules\Hr\Support\Announcements\AnnouncementTemplate;
use Modules\Hr\Support\Announcements\AnnouncementTemplates;
use Modules\Hr\Support\Announcements\HolidayClosure;
use Symfony\Component\HttpFoundation\StreamedResponse;

/*
 * The ready-made announcements (Modules\Hr\Support\Announcements): pick one,
 * fill the few things that change, read the email as it will go, then copy it
 * or open it as a new message. Opened by the `announcement-compose` event from
 * the Message templates page and from a person's profile (name already in), for
 * whoever holds message-templates.use. Nothing is sent by the system. The
 * wording is Arabic whatever the interface language.
 */
new class extends Component {
    public bool $open = false;

    /** The chosen template's key, '' while choosing. */
    public string $template = '';

    /** @var array<string, mixed> */
    public array $values = [];

    public string $to = '';

    public string $cc = '';

    /** Written once, added at the end of every email (kept on this computer by the page, not on the server). */
    public string $signature = '';

    /**
     * @param  array<string, mixed>  $prefill  values to start with (a person's name, say)
     */
    #[On('announcement-compose')]
    public function compose(?string $template = null, array $prefill = []): void
    {
        Gate::authorize('use', MessageTemplate::class);

        $this->reset(['template', 'values', 'to', 'cc']);
        $this->resetValidation();
        $this->open = true;

        if ($template !== null && AnnouncementTemplates::find($template) !== null) {
            $this->choose($template, $prefill);
        }
    }

    /**
     * @param  array<string, mixed>  $prefill
     */
    public function choose(string $key, array $prefill = []): void
    {
        Gate::authorize('use', MessageTemplate::class);

        $template = AnnouncementTemplates::find($key);
        abort_unless($template, 404);

        $values = $template->defaults();

        // Only the template's own fields, and only text: the event may come from the page.
        foreach ($prefill as $name => $value) {
            if (array_key_exists($name, $values) && is_scalar($value)) {
                $values[$name] = is_bool($values[$name]) ? (bool) $value : mb_substr((string) $value, 0, 500);
            }
        }

        $this->template = $key;
        $this->values = $values;
        ['to' => $this->to, 'cc' => $this->cc] = $template->recipients();
    }

    /** The whole message as a file Outlook opens as a new message (see AnnouncementTemplates::eml). */
    public function downloadEml(): StreamedResponse
    {
        Gate::authorize('use', MessageTemplate::class);

        $template = $this->current;
        abort_unless($template, 404);

        $message = $this->messageFor($template);
        $eml = AnnouncementTemplates::eml($this->to, $this->cc, $message['subject'], $message['body']);
        abort_if($eml === null, 422);

        return response()->streamDownload(function () use ($eml): void {
            echo $eml;
        }, $template->key().'.eml', ['Content-Type' => 'message/rfc822']);
    }

    public function updatedSignature(): void
    {
        $this->signature = mb_substr($this->signature, 0, 1000);
    }

    public function back(): void
    {
        $this->reset(['template', 'values', 'to', 'cc']);
    }

    public function close(): void
    {
        $this->open = false;
    }

    public function updatedValues(mixed $value, string $field): void
    {
        $template = $this->current;

        if (! $template) {
            return;
        }

        // Nothing but the template's fields is kept, whatever the browser sends.
        $this->values = array_intersect_key($this->values, $template->defaults());
        $this->values = $template->updated($field, $this->values);
    }

    #[Computed]
    public function current(): ?AnnouncementTemplate
    {
        return AnnouncementTemplates::find($this->template ?: null);
    }

    /**
     * The email as it will go, what is wrong with it, and how to open it.
     *
     * @return array{subject: string, body: string, problems: list<string>, mailto: array{url: string, trimmed: bool}|null}|null
     */
    #[Computed]
    public function message(): ?array
    {
        $template = $this->current;

        if (! $template) {
            return null;
        }

        $rendered = $this->messageFor($template);
        $problems = [
            ...array_map(fn (string $label): string => __('hr_messages.announcements.problems.missing', ['field' => $label]), $template->missing($this->values)),
            ...($template instanceof HolidayClosure ? $template->problems($this->values) : []),
        ];

        foreach ([$this->to, $this->cc] as $list) {
            foreach (AnnouncementTemplates::invalidAddresses($list) as $address) {
                $problems[] = __('hr_messages.announcements.problems.address', ['address' => $address]);
            }
        }

        if (AnnouncementTemplates::addresses($this->to) === []) {
            $problems[] = __('hr_messages.announcements.problems.no-to');
        }

        return [
            ...$rendered,
            'problems' => $problems,
            'mailto' => AnnouncementTemplates::mailto($this->to, $this->cc, $rendered['subject'], $rendered['body']),
        ];
    }

    /**
     * The email for these values with the signature under it. Outlook adds its own
     * signature only to a message it composes itself, never to one opened from a link
     * or a file, so it is written here.
     *
     * @return array{subject: string, body: string}
     */
    private function messageFor(AnnouncementTemplate $template): array
    {
        $message = $template->render($this->values);
        $signature = trim(str_replace("\r\n", "\n", $this->signature));

        if ($signature !== '') {
            $message['body'] .= "\n\n".$signature;
        }

        return $message;
    }
};
?>

@php
    $current = $this->current;
    $message = $this->message;
@endphp

<div data-hr-announcements>
    <x-ui.modal wire:model="open" size="xl" :title="$current ? $current->label() : __('hr_messages.announcements.title')" close="close">
        @if ($open)
            @if (! $current)
                <p class="mb-4 text-sm text-secondary-medium dark:text-ink-muted">{{ __('hr_messages.announcements.lead') }}</p>
                <ul class="grid gap-3 sm:grid-cols-2" data-test="announcement-list">
                    @foreach (AnnouncementTemplates::all() as $key => $template)
                        <li wire:key="announcement-choice-{{ $key }}">
                            <button type="button" wire:click="choose('{{ $key }}')" data-test="announcement-{{ $key }}"
                                    class="flex h-full w-full flex-col items-start gap-1 rounded-lg border border-line p-4 text-start hover:bg-surface-2">
                                <span class="font-semibold">{{ $template->label() }}</span>
                                <span class="text-sm text-secondary-medium dark:text-ink-muted">{{ $template->description() }}</span>
                            </button>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="mb-4">
                    <x-ui.button tone="ghost" size="sm" icon="arrow-uturn-left" wire:click="back">{{ __('hr_messages.announcements.other') }}</x-ui.button>
                </div>

                <div class="grid gap-6 lg:grid-cols-[minmax(0,22rem)_minmax(0,1fr)]">
                    {{-- The fields. Arabic wording, so the inputs are written right to left. --}}
                    <div class="grid content-start gap-y-3" data-test="announcement-fields">
                        @foreach ($current->fields() as $field)
                            @continue (! $current->isShown($field, $values))
                            @php($label = $current->fieldLabel($field['name']))
                            <div wire:key="ann-{{ $current->key() }}-{{ $field['name'] }}">
                                @switch ($field['type'])
                                    @case('toggle')
                                        <flux:checkbox wire:model.live="values.{{ $field['name'] }}" :label="$label" />
                                        @break
                                    @case('choice')
                                        <x-ui.form-select wire:model.live="values.{{ $field['name'] }}" :label="$label" :required="$field['required'] ?? false">
                                            @foreach ($field['options'] as $optionValue => $optionLabel)
                                                <option value="{{ $optionValue }}">{{ $optionLabel }}</option>
                                            @endforeach
                                        </x-ui.form-select>
                                        @break
                                    @case('textarea')
                                        <x-ui.form-textarea wire:model.live.debounce.400ms="values.{{ $field['name'] }}" :label="$label" rows="2" dir="rtl" :required="$field['required'] ?? false"
                                            :help="isset($field['hint']) ? __('hr_messages.announcements.hints.'.$field['hint']) : null" />
                                        @break
                                    @case('date')
                                        <x-ui.form-input type="date" wire:model.live="values.{{ $field['name'] }}" :label="$label" :required="$field['required'] ?? false"
                                            :help="isset($field['hint']) ? __('hr_messages.announcements.hints.'.$field['hint']) : null" />
                                        @break
                                    @default
                                        <x-ui.form-input wire:model.live.debounce.400ms="values.{{ $field['name'] }}" :label="$label" dir="{{ $field['dir'] ?? 'rtl' }}" maxlength="500" :required="$field['required'] ?? false"
                                            :help="isset($field['hint']) ? __('hr_messages.announcements.hints.'.$field['hint']) : null" />
                                @endswitch
                            </div>
                        @endforeach

                        {{-- The same signature for every email, kept in this browser. --}}
                        <div wire:key="ann-signature" class="border-t border-dashed border-line pt-3"
                             x-data="{
                                 init() {
                                     try {
                                         const saved = localStorage.getItem('hub-hr-announcement-signature');
                                         if (saved && ! $wire.signature) $wire.set('signature', saved);
                                     } catch (e) {}
                                     $wire.$watch('signature', value => {
                                         try { localStorage.setItem('hub-hr-announcement-signature', value ?? ''); } catch (e) {}
                                     });
                                 },
                             }">
                            <x-ui.form-textarea wire:model.live.debounce.600ms="signature" :label="__('hr_messages.announcements.signature')" rows="3" dir="rtl" maxlength="1000"
                                :help="__('hr_messages.announcements.hints.signature')" data-test="announcement-signature" />
                        </div>
                    </div>

                    {{-- The email as it will go. --}}
                    <section class="min-w-0 rounded-lg border border-line p-4" aria-live="polite" data-test="announcement-preview">
                        <div class="grid gap-y-3 border-b border-line pb-4">
                            <x-ui.form-input wire:model.live.debounce.400ms="to" :label="__('hr_messages.announcements.to')" dir="ltr" data-test="announcement-to" />
                            <x-ui.form-input wire:model.live.debounce.400ms="cc" :label="__('hr_messages.announcements.cc')" dir="ltr" data-test="announcement-cc" />
                            <div class="flex min-w-0 items-center gap-2" x-data="{ copied: false }">
                                <span class="min-w-0 flex-1 font-semibold [overflow-wrap:anywhere]" dir="rtl" data-test="announcement-subject">{{ $message['subject'] }}</span>
                                <x-ui.button size="sm" type="button" data-copy="{{ $message['subject'] }}"
                                    x-on:click="navigator.clipboard?.writeText($el.dataset.copy); copied = true; setTimeout(() => copied = false, 2000)">
                                    <span x-show="! copied">{{ __('hr_messages.announcements.copy') }}</span>
                                    <span x-show="copied" x-cloak>{{ __('hr_messages.announcements.copied-subject') }}</span>
                                </x-ui.button>
                            </div>
                        </div>

                        <div class="whitespace-pre-wrap pt-4 leading-relaxed" dir="rtl" lang="ar" data-test="announcement-body">{{ $message['body'] }}</div>

                        @if ($message['problems'])
                            <ul class="mt-4 space-y-1 text-sm text-status-critical" data-test="announcement-problems">
                                @foreach ($message['problems'] as $problem)
                                    <li>{{ $problem }}</li>
                                @endforeach
                            </ul>
                        @endif

                        <div class="mt-5 flex flex-wrap gap-3" x-data="{ copied: false }">
                            <x-ui.button tone="primary" type="button" data-copy="{{ $message['body'] }}" data-test="announcement-copy-body"
                                x-on:click="navigator.clipboard?.writeText($el.dataset.copy); copied = true; setTimeout(() => copied = false, 2000)">
                                <span x-show="! copied">{{ __('hr_messages.announcements.copy-body') }}</span>
                                <span x-show="copied" x-cloak>{{ __('hr_messages.announcements.copied-body') }}</span>
                            </x-ui.button>
                            @if ($message['mailto'])
                                {{-- A short email opens as a link. A long one cannot travel in a link (Windows refuses
                                     past ~2000 characters, Arabic costs ~6 a letter), so it comes as a message file
                                     that Outlook opens as a new message with the whole text. --}}
                                @if ($message['mailto']['trimmed'])
                                    <x-ui.button type="button" wire:click="downloadEml" wire:loading.attr="disabled" wire:target="downloadEml" data-test="announcement-mailto">{{ __('hr_messages.announcements.open-mail') }}</x-ui.button>
                                @else
                                    <x-ui.button :href="$message['mailto']['url']" data-test="announcement-mailto">{{ __('hr_messages.announcements.open-mail') }}</x-ui.button>
                                @endif
                            @endif
                        </div>
                        @if ($message['mailto'] && $message['mailto']['trimmed'])
                            <p class="mt-3 text-sm text-secondary-medium dark:text-ink-muted" data-test="announcement-long-help">{{ __('hr_messages.announcements.long-help') }}</p>
                        @endif
                        <p class="mt-3 text-sm text-secondary-medium dark:text-ink-muted">{{ __('hr_messages.announcements.nothing-sent') }}</p>
                    </section>
                </div>
            @endif
        @endif
    </x-ui.modal>
</div>
