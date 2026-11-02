{{-- The question box, shared by the page and the panel (livewire/assistant/chat). --}}
@php
    $fieldId = 'assistant-question-'.$mode;
@endphp

<form x-on:submit.prevent="submit()">
    <label for="{{ $fieldId }}" class="sr-only">{{ __('assistant.question') }}</label>
    <textarea
        id="{{ $fieldId }}"
        wire:model="question"
        rows="2"
        maxlength="1000"
        @class(['w-full rounded-control border border-line bg-card px-3 py-2 text-body text-ink placeholder:text-ink-3 shadow-e1 transition focus:border-primary focus:outline-none focus:ring-2 focus:ring-secondary-light', 'resize-y' => ! $isPanel, 'resize-none' => $isPanel])
        placeholder="{{ __('assistant.placeholder') }}"
        aria-describedby="{{ $fieldId }}-hint"
        x-on:keydown.enter="if (! $event.shiftKey && ! $event.isComposing) { $event.preventDefault(); submit() }"
        x-bind:readonly="!! pending"
    ></textarea>
    <x-forms.input-error field="question" :input-id="$fieldId" />

    <div class="mt-2 flex flex-wrap items-center justify-between gap-2">
        <p id="{{ $fieldId }}-hint" @class(['text-caption text-ink-3', 'sr-only' => $isPanel])>{{ __('assistant.hint') }}</p>
        <x-assistant.button variant="primary" :size="$isPanel ? 'sm' : 'md'" type="submit" target="send" class="ms-auto" x-bind:disabled="!! pending">
            {{ __('assistant.send') }}
        </x-assistant.button>
    </div>
</form>
