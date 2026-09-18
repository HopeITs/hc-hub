<x-slot:title>{{ __('recruitment.application_form.title') }}</x-slot:title>

<div>
    <livewire:layout.breadcrumbs
        :title="__('recruitment.application_form.title')"
        :crumbs="[
            ['label' => __('recruitment.job_post.campaign').' #'.$campaign->id],
            ['label' => __('recruitment.application_form.title')],
        ]"
    />

    <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-body text-secondary-medium">{{ __('recruitment.application_form.subtitle') }}</p>
            <p class="mt-1 text-small text-neutral-500">{{ __('recruitment.job_post.approved_request') }}: <span class="font-semibold text-neutral-800">{{ $campaign->vacancy->createdFromRequest->request_number }}</span></p>
        </div>
        <div class="flex gap-2 text-small">
            <span class="rounded-full bg-neutral-100 px-3 py-1 font-semibold text-neutral-700">{{ __('recruitment.job_post.version') }} #{{ $versionNumber }}</span>
            <span class="rounded-full px-3 py-1 font-semibold {{ $versionStatus === 'published' ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' }}">
                {{ $versionStatus === 'published' ? __('recruitment.job_post.status_published') : __('recruitment.job_post.status_draft') }}
            </span>
            @if ($publishedVersionNumber)
                <span class="rounded-full bg-primary/10 px-3 py-1 font-semibold text-primary">{{ __('recruitment.job_post.published_version') }} #{{ $publishedVersionNumber }}</span>
            @endif
        </div>
    </div>

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
        <section class="rounded-card border border-neutral-200 bg-white p-5 shadow-e1">
            <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-subtitle font-bold text-neutral-900">{{ __('recruitment.application_form.fields') }}</h2>
                    <p class="text-small text-neutral-500">{{ __('recruitment.application_form.fields_help') }}</p>
                </div>
                @if ($availableFields->isNotEmpty())
                    <div class="flex flex-wrap gap-2">
                        @foreach ($availableFields as $key => $definition)
                            <button type="button" wire:click="addField('{{ $key }}')" class="rounded-control border border-primary px-3 py-1.5 text-small font-semibold text-primary hover:bg-primary/5">
                                + {{ $definition['label'][app()->getLocale()] ?? $definition['label']['en'] }}
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="space-y-3">
                @foreach ($fields as $index => $field)
                    <article wire:key="form-field-{{ $field['key'] }}" class="rounded-control border border-neutral-200 bg-neutral-50 p-4">
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <div>
                                <span class="font-semibold text-neutral-900">{{ $field['key'] }}</span>
                                <span class="ms-2 rounded-full bg-white px-2 py-0.5 text-small text-neutral-500">{{ $field['type'] }}</span>
                            </div>
                            <div class="flex gap-1">
                                <button type="button" wire:click="moveField({{ $index }}, -1)" class="rounded px-2 py-1 text-neutral-600 hover:bg-white" aria-label="{{ __('recruitment.application_form.move_up') }}">↑</button>
                                <button type="button" wire:click="moveField({{ $index }}, 1)" class="rounded px-2 py-1 text-neutral-600 hover:bg-white" aria-label="{{ __('recruitment.application_form.move_down') }}">↓</button>
                                @unless ($field['locked'])
                                    <button type="button" wire:click="removeField({{ $index }})" class="rounded px-2 py-1 text-red-700 hover:bg-red-50">{{ __('recruitment.application_form.remove') }}</button>
                                @endunless
                            </div>
                        </div>

                        <div class="grid gap-3 md:grid-cols-2">
                            <div>
                                <x-forms.input-label for="field-en-{{ $index }}" required>{{ __('recruitment.application_form.label_en') }}</x-forms.input-label>
                                <x-forms.input id="field-en-{{ $index }}" model="fields.{{ $index }}.label.en" dir="ltr" required />
                                <x-error-span>{{ $errors->first("fields.$index.label.en") }}</x-error-span>
                            </div>
                            <div>
                                <x-forms.input-label for="field-ar-{{ $index }}" required>{{ __('recruitment.application_form.label_ar') }}</x-forms.input-label>
                                <x-forms.input id="field-ar-{{ $index }}" model="fields.{{ $index }}.label.ar" dir="rtl" required />
                                <x-error-span>{{ $errors->first("fields.$index.label.ar") }}</x-error-span>
                            </div>
                        </div>

                        <label class="mt-3 inline-flex items-center gap-2 text-small text-neutral-700">
                            <input type="checkbox" wire:model="fields.{{ $index }}.required" @disabled($field['locked']) class="rounded border-neutral-300 text-primary focus:ring-primary">
                            {{ __('recruitment.application_form.required') }}
                            @if ($field['locked']) <span class="text-neutral-400">({{ __('recruitment.application_form.protected') }})</span> @endif
                        </label>
                    </article>
                @endforeach
            </div>
        </section>

        <aside class="h-fit rounded-card border border-neutral-200 bg-white p-5 shadow-e1">
            <x-forms.input-label for="privacy-version" required>{{ __('recruitment.application_form.privacy_version') }}</x-forms.input-label>
            <x-forms.input id="privacy-version" model="privacyNoticeVersion" dir="ltr" required />
            <x-error-span>{{ $errors->first('privacyNoticeVersion') }}</x-error-span>
            <p class="mt-2 text-small text-neutral-500">{{ __('recruitment.application_form.privacy_help') }}</p>

            <div class="mt-6 grid gap-2">
                <x-primary-button type="button" full wire:click="saveDraft" wire:target="saveDraft">{{ __('recruitment.job_post.save_draft') }}</x-primary-button>
                <x-primary-button type="button" full wire:click="publish" wire:target="publish" wire:confirm="{{ __('recruitment.application_form.publish_confirm') }}" class="!bg-neutral-800 hover:!bg-neutral-700">
                    {{ __('recruitment.job_post.publish') }}
                </x-primary-button>
            </div>

            @if ($notice)
                <p class="mt-4 rounded-control px-3 py-2 text-small {{ $noticeType === 'error' ? 'bg-red-50 text-red-800' : ($noticeType === 'info' ? 'bg-blue-50 text-blue-800' : 'bg-green-50 text-green-800') }}" role="status">
                    {{ $notice }}
                </p>
                @if ($hasConflict)
                    <button type="button" wire:click="reloadLatest" class="mt-2 text-small font-semibold text-primary underline">{{ __('recruitment.job_post.reload_latest') }}</button>
                @endif
            @endif

            <div class="mt-6 rounded-control bg-primary/5 p-3 text-small leading-6 text-secondary-medium">
                {{ __('recruitment.application_form.safety_note') }}
            </div>
        </aside>
    </div>
</div>
