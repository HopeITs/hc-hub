@props(['options', 'submitLabel' => null])

{{-- The request form with its change-request note and sticky action bar; the page supplies the header. --}}
@php
    $offerSubmit = (bool) ($options['canSubmit'] ?? false);
    $changeRequest = $options['changeRequest'] ?? null;
    $muted = 'text-secondary-medium dark:text-ink-muted';
@endphp

<form wire:submit="save" novalidate class="space-y-6">
    @if (filled($changeRequest))
        <x-ui.panel :clip="false" class="border-s-4 border-s-primary p-4 dark:border-s-brand" role="note">
            <p class="font-semibold">{{ __('hr_recruitment.changes_requested_edit_title') }}</p>
            <p class="mt-1 text-sm {{ $muted }}">{{ __('hr_recruitment.changes_requested_edit_hint') }}</p>
            @if (filled($changeRequest['fields'] ?? null))
                <ul class="mt-3 flex flex-wrap gap-1.5" aria-label="{{ __('hr_recruitment.form_ui.requested_fields') }}">
                    @foreach ($changeRequest['fields'] as $requestedField)
                        <li class="rounded-md bg-secondary-soft px-2 py-0.5 text-sm dark:bg-surface-2">{{ $requestedField['label'] }}</li>
                    @endforeach
                </ul>
            @endif
            @if (filled($changeRequest['comment'] ?? null))
                <div class="mt-3 rounded-md bg-secondary-soft/50 p-3 dark:bg-surface-2">
                    <p class="text-xs font-semibold {{ $muted }}">{{ __('hr_recruitment.approver_comment') }}</p>
                    <p class="mt-1 whitespace-pre-line text-sm" dir="auto">{{ $changeRequest['comment'] }}</p>
                </div>
            @endif
        </x-ui.panel>
    @endif

    {{ $slot }}

    <x-hr::recruitment.request-fields
        :data="$options['data']" :editing="$options['editing']" :positions="$options['positions']" :vacancies="$options['vacancies']"
        :cities="$options['cities']" :programs="$options['programs']" :centers="$options['centers']" :center-required="$options['centerRequired']"
        :departments="$options['departments']" :job-levels="$options['jobLevels']" :contract-types="$options['contractTypes']"
        :locked-fields="$options['lockedFields']" :requested-fields="$options['requestedFields']" :form="$options['form']" />

    <footer class="sticky bottom-0 z-20 rounded-panel border border-panel-border bg-white px-4 py-3 shadow-card dark:bg-surface-1 dark:shadow-raised">
        <div class="flex flex-wrap items-center justify-between gap-x-6 gap-y-3">
            <p class="min-w-0 max-w-[60ch] flex-1 basis-64 text-sm {{ $muted }}">
                {{ $offerSubmit ? __('hr_recruitment.form_ui.footer_note_submit') : __('hr_recruitment.draft_help') }}
            </p>
            <div class="flex flex-wrap items-center gap-2">
                <x-ui.button type="submit" :tone="$offerSubmit ? 'secondary' : 'primary'" wire:loading.attr="disabled" wire:target="save,saveAndSubmit">{{ __('hr_recruitment.save_draft') }}</x-ui.button>
                @if ($offerSubmit)
                    <x-ui.button tone="primary" icon="paper-airplane" wire:click="saveAndSubmit" wire:loading.attr="disabled" wire:target="save,saveAndSubmit">{{ $submitLabel ?? __('hr_recruitment.form_ui.save_and_submit') }}</x-ui.button>
                @endif
            </div>
        </div>
    </footer>
</form>
