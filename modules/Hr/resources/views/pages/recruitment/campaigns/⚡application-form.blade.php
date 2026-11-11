<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Modules\Hr\Actions\Recruitment\RecruitmentApplicationFormService as Forms;
use Modules\Hr\Livewire\Concerns\Recruitment\AuthorizesRecruitment;
use Modules\Hr\Models\ApplicationForm;
use Modules\Hr\Models\ApplicationFormVersion;
use Modules\Hr\Models\HiringCampaign;
use Modules\Hr\Support\Recruitment\Exceptions\StaleRecruitmentDraftException;

/*
 * The applicant-facing form of a campaign: questions from the library or
 * custom ones, grouped in fixed sections, bilingual, versioned by the
 * service against the fingerprint the editor was opened at.
 */
new #[Layout('layouts::desk'), Title('hr_recruitment.hub.titles.application_form')] class extends Component {
    use AuthorizesRecruitment;

    #[Locked]
    public int $campaignId;

    #[Locked]
    public int $formId;

    /** @var list<array<string, mixed>> */
    public array $fields = [];

    public string $privacyNoticeVersion = 'v1';

    public string $expandedSection = 'vacancy';

    public string $customType = 'text';

    public string $customSection = 'personal';

    public string $customLabelEn = '';

    public string $customLabelAr = '';

    public string $customHelpEn = '';

    public string $customHelpAr = '';

    public string $customOptionsEn = '';

    public string $customOptionsAr = '';

    public bool $customRequired = false;

    public string $libraryField = '';

    public string $selectedTemplateVersionId = '';

    #[Locked]
    public int $versionId;

    #[Locked]
    public string $versionFingerprint = '';

    public int $versionNumber = 1;

    public string $versionStatus = 'draft';

    public ?int $publishedVersionNumber = null;

    public bool $hasConflict = false;

    public bool $justPublished = false;

    public bool $confirmPublish = false;

    public function mount(int $campaignId, Forms $service): void
    {
        $this->requirePermission('recruitment-forms.manage');
        $this->campaignId = $campaignId;
        $campaign = $this->campaign();
        $existing = $campaign->applicationForm()->first();
        $existing ? Gate::authorize('manage', $existing) : Gate::authorize('create', [ApplicationForm::class, $campaign]);

        try {
            $this->applyState($service->openEditor($campaign, $this->viewer()));
        } catch (DomainException $exception) {
            abort(409, $exception->getMessage());
        }

        $this->expandedSection = $this->firstUsedSection();
    }

    public function campaign(): HiringCampaign
    {
        $campaign = HiringCampaign::query()->visibleTo($this->viewer())
            ->with(['vacancy.position', 'vacancy.createdFromRequest']) // full rows: the services read them
            ->findOrFail($this->campaignId);
        Gate::authorize('view', $campaign);

        return $campaign;
    }

    public function form(): ApplicationForm
    {
        return ApplicationForm::query()->whereKey($this->formId)->where('campaign_id', $this->campaignId)->firstOrFail();
    }

    public function updating(string $name): void
    {
        if (str_starts_with($name, 'fields') || $name === 'privacyNoticeVersion') {
            $this->justPublished = false;
        }
    }

    public function addLibraryField(): void
    {
        Gate::authorize('manage', $this->form());
        $key = $this->libraryField;

        if (! isset(Forms::FIELD_CATALOG[$key]) || collect($this->fields)->contains('key', $key)) {
            return;
        }

        $definition = Forms::FIELD_CATALOG[$key];
        $this->fields[] = ['key' => $key, ...$definition];
        $this->expandedSection = (string) $definition['section'];
        $this->libraryField = '';
    }

    public function addCustomField(Forms $service): void
    {
        Gate::authorize('manage', $this->form());
        $rules = [
            'customType' => ['required', Rule::in(array_keys(Forms::TYPE_CATALOG))],
            'customSection' => ['required', Rule::in(array_keys(Forms::SECTION_CATALOG))],
            'customLabelEn' => ['required', 'string', 'max:180'],
            'customLabelAr' => ['required', 'string', 'max:180'],
            'customHelpEn' => ['nullable', 'string', 'max:500'],
            'customHelpAr' => ['nullable', 'string', 'max:500'],
            'customRequired' => ['boolean'],
        ];

        if (in_array($this->customType, ['select', 'radio'], true)) {
            $rules['customOptionsEn'] = ['required', 'string', 'max:2000'];
            $rules['customOptionsAr'] = ['nullable', 'string', 'max:2000'];
        }

        $this->validate($rules);

        $english = $this->lines($this->customOptionsEn);
        $arabic = $this->lines($this->customOptionsAr);
        $options = in_array($this->customType, ['select', 'radio'], true)
            ? collect($english)->take(20)->values()->map(fn (string $label, int $index): array => ['value' => 'option_'.($index + 1), 'label' => ['en' => $label, 'ar' => $arabic[$index] ?? $label]])->all()
            : [];

        $this->fields[] = $service->makeCustomField($this->customType, $this->customSection, $this->customLabelEn, $this->customLabelAr, $this->customHelpEn, $this->customHelpAr, $this->customRequired, $options);
        $this->expandedSection = $this->customSection;
        $this->reset(['customLabelEn', 'customLabelAr', 'customHelpEn', 'customHelpAr', 'customOptionsEn', 'customOptionsAr', 'customRequired']);
        $this->customType = 'text';
    }

    public function applyReferenceTemplate(Forms $service): void
    {
        Gate::authorize('manage', $this->form());
        $this->fields = $service->defaultFields();
        $this->expandedSection = 'vacancy';
        $this->resetValidation();
        $this->toast(__('hr_recruitment.application_form.template_applied'));
    }

    public function applyPublishedTemplate(Forms $service): void
    {
        Gate::authorize('manage', $this->form());
        $this->validate(['selectedTemplateVersionId' => ['required', 'integer', 'min:1']]);

        try {
            $template = $service->copyPublishedTemplate($this->campaign(), (int) $this->selectedTemplateVersionId);
            $this->fields = $template['fields'];
            $this->privacyNoticeVersion = $template['privacy_notice_version'];
            $this->expandedSection = $this->firstUsedSection();
            $this->resetValidation();
            $this->toast(__('hr_recruitment.application_form.reused_success'));
        } catch (DomainException) {
            $this->toast(__('hr_recruitment.application_form.reuse_unavailable'), false);
        }
    }

    public function removeField(int $index): void
    {
        Gate::authorize('manage', $this->form());

        if (! isset($this->fields[$index]) || ($this->fields[$index]['locked'] ?? true) === true) {
            return;
        }

        array_splice($this->fields, $index, 1);
    }

    /** Moves a question within its own section only. */
    public function moveField(int $index, int $direction): void
    {
        Gate::authorize('manage', $this->form());

        if (! isset($this->fields[$index]) || ! in_array($direction, [-1, 1], true)) {
            return;
        }

        $section = $this->fields[$index]['section'];
        $candidate = $index + $direction;

        while (isset($this->fields[$candidate]) && $this->fields[$candidate]['section'] !== $section) {
            $candidate += $direction;
        }

        if (isset($this->fields[$candidate])) {
            [$this->fields[$index], $this->fields[$candidate]] = [$this->fields[$candidate], $this->fields[$index]];
        }
    }

    public function addOption(int $fieldIndex): void
    {
        Gate::authorize('manage', $this->form());

        if (! isset($this->fields[$fieldIndex]) || ! in_array($this->fields[$fieldIndex]['type'], ['select', 'radio'], true)) {
            return;
        }

        $number = count($this->fields[$fieldIndex]['options'] ?? []) + 1;
        $this->fields[$fieldIndex]['options'][] = ['value' => 'option_'.$number, 'label' => ['en' => 'Option '.$number, 'ar' => 'الخيار '.$number]];
    }

    public function removeOption(int $fieldIndex, int $optionIndex): void
    {
        Gate::authorize('manage', $this->form());

        if (isset($this->fields[$fieldIndex]['options'][$optionIndex]) && count($this->fields[$fieldIndex]['options']) > 1) {
            array_splice($this->fields[$fieldIndex]['options'], $optionIndex, 1);
        }
    }

    public function toggleSection(string $section): void
    {
        if (isset(Forms::SECTION_CATALOG[$section])) {
            $this->expandedSection = $this->expandedSection === $section ? '' : $section;
        }
    }

    public function saveDraft(Forms $service): void
    {
        $form = $this->form();
        Gate::authorize('manage', $form);
        $this->validateEditor();

        try {
            $version = $service->saveDraft($form, $this->fields, $this->privacyNoticeVersion, $this->versionId, $this->versionFingerprint);
            $this->syncVersion($version, $service);
            $this->toast(__('hr_recruitment.application_form.saved'));
        } catch (StaleRecruitmentDraftException) {
            $this->conflict();
        }
    }

    public function publish(Forms $service): void
    {
        $this->confirmPublish = false;
        $form = $this->form();
        Gate::authorize('publish', $form);
        $this->validateEditor();

        try {
            $version = $service->publish($form, $this->fields, $this->privacyNoticeVersion, $this->viewer(), $this->versionId, $this->versionFingerprint);
            $this->syncVersion($version, $service);
            $this->toast(__('hr_recruitment.application_form.published', ['version' => $version->version_no]));
            $this->justPublished = true;
        } catch (StaleRecruitmentDraftException) {
            $this->conflict();
        }
    }

    public function reloadLatest(Forms $service): void
    {
        Gate::authorize('manage', $this->form());
        $this->applyState($service->openEditor($this->campaign(), $this->viewer()));
        $this->expandedSection = $this->firstUsedSection();
        $this->hasConflict = false;
        $this->toast(__('hr_recruitment.application_form.reloaded'));
    }

    private function validateEditor(): void
    {
        $this->validate([
            'privacyNoticeVersion' => ['required', 'string', 'max:255'],
            'fields' => ['required', 'array', 'min:5', 'max:60'],
            'fields.*.label.en' => ['required', 'string', 'max:180'],
            'fields.*.label.ar' => ['required', 'string', 'max:180'],
            'fields.*.help.en' => ['nullable', 'string', 'max:500'],
            'fields.*.help.ar' => ['nullable', 'string', 'max:500'],
            'fields.*.required' => ['boolean'],
            'fields.*.section' => ['required', Rule::in(array_keys(Forms::SECTION_CATALOG))],
            'fields.*.type' => ['required', Rule::in(array_keys(Forms::TYPE_CATALOG))],
            'fields.*.options' => ['array', 'max:20'],
            'fields.*.options.*.label.en' => ['required_with:fields.*.options', 'string', 'max:120'],
            'fields.*.options.*.label.ar' => ['required_with:fields.*.options', 'string', 'max:120'],
        ]);
    }

    /** @return list<string> */
    private function lines(string $value): array
    {
        return collect(preg_split('/\R/u', $value) ?: [])->map(fn (string $line): string => trim($line))->filter()->values()->all();
    }

    /** @param  array<string, mixed>  $state */
    private function applyState(array $state): void
    {
        $this->formId = (int) $state['application_form']->getKey();
        $this->fields = $state['fields'];
        $this->privacyNoticeVersion = $state['privacy_notice_version'];
        $this->versionFingerprint = $state['fingerprint'];
        $this->setVersionState($state['version']);
    }

    private function syncVersion(ApplicationFormVersion $version, Forms $service): void
    {
        $this->fields = $service->normalizeFields($version->fields_json);
        $this->versionFingerprint = $service->fingerprint($version);
        $this->setVersionState($version);
        $this->hasConflict = false;
    }

    private function setVersionState(ApplicationFormVersion $version): void
    {
        $this->versionId = (int) $version->getKey();
        $this->versionNumber = (int) $version->version_no;
        $this->versionStatus = (string) $version->status;
        $this->publishedVersionNumber = $this->form()->currentVersion()->value('version_no');
    }

    private function firstUsedSection(): string
    {
        foreach (array_keys(Forms::SECTION_CATALOG) as $section) {
            if (collect($this->fields)->contains('section', $section)) {
                return $section;
            }
        }

        return 'personal';
    }

    private function conflict(): void
    {
        $this->hasConflict = true;
        $this->toast(__('hr_recruitment.application_form.conflict'), false);
    }
};
?>

@php
    $campaign = $this->campaign();
    $form = $this->form();
    $locale = app()->getLocale() === 'ar' ? 'ar' : 'en';
    $muted = 'text-secondary-medium dark:text-ink-muted';
    $used = array_column($fields, 'key');
    $library = collect(Forms::FIELD_CATALOG)->except($used);
    $templates = app(Forms::class)->availableTemplates($campaign);
    $next = $justPublished ? ['message' => __('hr_recruitment.publishing.next_after_form'), 'label' => __('hr_recruitment.publishing.back'), 'url' => route('hr.recruitment.campaigns.show', ['campaignId' => $campaign->id])] : null;
@endphp

<div class="space-y-6">
    <x-hr::recruitment.editor-context :campaign="$campaign" :title="__('hr_recruitment.application_form.title')"
        :version-number="$versionNumber" :version-status="$versionStatus" :published-version-number="$publishedVersionNumber" :next="$next" />

    @if ($hasConflict)
        <x-ui.panel :clip="false" class="flex flex-wrap items-center justify-between gap-3 p-4" role="alert">
            <p class="text-sm text-status-critical">{{ __('hr_recruitment.application_form.conflict') }}</p>
            <x-ui.button tone="secondary" wire:click="reloadLatest">{{ __('hr_recruitment.job_post.reload_latest') }}</x-ui.button>
        </x-ui.panel>
    @endif

    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="min-w-0 space-y-4">
            @error('fields')<p class="text-sm text-status-critical" role="alert">{{ $message }}</p>@enderror
            @foreach (Forms::SECTION_CATALOG as $sectionKey => $section)
                @php
                    $inSection = collect($fields)->filter(fn ($field) => $field['section'] === $sectionKey);
                    $open = $expandedSection === $sectionKey;
                @endphp
                <x-ui.panel :clip="false" wire:key="section-{{ $sectionKey }}">
                    <button type="button" wire:click="toggleSection('{{ $sectionKey }}')" class="flex w-full items-center justify-between gap-3 px-4 py-3 text-start" aria-expanded="{{ $open ? 'true' : 'false' }}">
                        <span>
                            <span class="block font-semibold">{{ $section['label'][$locale] }} <span class="text-sm font-normal tabular-nums {{ $muted }}">({{ $inSection->count() }})</span></span>
                            <span class="block text-xs {{ $muted }}">{{ $section['description'][$locale] }}</span>
                        </span>
                        <flux:icon :name="$open ? 'chevron-up' : 'chevron-down'" variant="mini" class="size-4" />
                    </button>
                    @if ($open)
                        <div class="space-y-3 border-t border-panel-border p-4">
                            @forelse ($inSection as $index => $field)
                                @php
                                    $locked = (bool) ($field['locked'] ?? false);
                                @endphp
                                <div class="rounded-md border border-panel-border p-3" wire:key="field-{{ $field['key'] }}">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <p class="text-xs font-semibold {{ $muted }}">{{ Forms::TYPE_CATALOG[$field['type']]['label'][$locale] ?? $field['type'] }}@if ($locked) · {{ __('hr_recruitment.application_form.protected') }}@endif</p>
                                        <div class="flex items-center gap-1">
                                            <x-ui.button size="sm" tone="ghost" icon="arrow-up" wire:click="moveField({{ $index }}, -1)" :aria-label="__('hr_recruitment.application_form.move_up')" />
                                            <x-ui.button size="sm" tone="ghost" icon="arrow-down" wire:click="moveField({{ $index }}, 1)" :aria-label="__('hr_recruitment.application_form.move_down')" />
                                            @unless ($locked)
                                                <x-ui.button size="sm" tone="ghost" icon="trash" wire:click="removeField({{ $index }})" wire:confirm="{{ __('hr_recruitment.application_form.remove_confirm') }}" :aria-label="__('hr_recruitment.application_form.remove')" />
                                            @endunless
                                        </div>
                                    </div>
                                    <div class="mt-2 grid gap-3 sm:grid-cols-2">
                                        <x-ui.form-input wire:model="fields.{{ $index }}.label.en" :label="__('hr_recruitment.application_form.label_en')" dir="ltr" required />
                                        <x-ui.form-input wire:model="fields.{{ $index }}.label.ar" :label="__('hr_recruitment.application_form.label_ar')" dir="rtl" required />
                                        <x-ui.form-input wire:model="fields.{{ $index }}.help.en" :label="__('hr_recruitment.application_form.help_en')" dir="ltr" />
                                        <x-ui.form-input wire:model="fields.{{ $index }}.help.ar" :label="__('hr_recruitment.application_form.help_ar')" dir="rtl" />
                                    </div>
                                    @unless ($locked)
                                        <div class="mt-2"><flux:checkbox wire:model.live="fields.{{ $index }}.required" :label="__('hr_recruitment.application_form.required')" /></div>
                                    @endunless
                                    @if (in_array($field['type'], ['select', 'radio'], true))
                                        <div class="mt-3 space-y-2">
                                            <p class="text-xs font-semibold">{{ __('hr_recruitment.application_form.options') }}</p>
                                            @foreach ($field['options'] ?? [] as $optionIndex => $option)
                                                <div class="flex items-end gap-2" wire:key="option-{{ $field['key'] }}-{{ $optionIndex }}">
                                                    <x-ui.form-input class="flex-1" wire:model="fields.{{ $index }}.options.{{ $optionIndex }}.label.en" :label="__('hr_recruitment.application_form.option_en')" dir="ltr" />
                                                    <x-ui.form-input class="flex-1" wire:model="fields.{{ $index }}.options.{{ $optionIndex }}.label.ar" :label="__('hr_recruitment.application_form.option_ar')" dir="rtl" />
                                                    <x-ui.button size="sm" tone="ghost" icon="x-mark" wire:click="removeOption({{ $index }}, {{ $optionIndex }})" :aria-label="__('hr_recruitment.application_form.remove')" />
                                                </div>
                                            @endforeach
                                            <x-ui.button size="sm" tone="secondary" icon="plus" wire:click="addOption({{ $index }})">{{ __('hr_recruitment.application_form.add_option') }}</x-ui.button>
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <p class="text-sm {{ $muted }}">{{ __('hr_recruitment.application_form.no_questions_section') }}</p>
                            @endforelse
                        </div>
                    @endif
                </x-ui.panel>
            @endforeach
        </div>

        <aside class="space-y-4 xl:sticky xl:top-24">
            <x-ui.panel :clip="false">
                <div class="grid gap-2 p-4">
                    <x-ui.form-input wire:model="privacyNoticeVersion" :label="__('hr_recruitment.application_form.privacy_version')" :help="__('hr_recruitment.application_form.privacy_help')" required />
                    <x-ui.button tone="secondary" wire:click="saveDraft" wire:loading.attr="disabled" wire:target="saveDraft">{{ __('hr_recruitment.job_post.save_draft') }}</x-ui.button>
                    @can('publish', $form)
                        <x-ui.button tone="primary" wire:click="$set('confirmPublish', true)">{{ __('hr_recruitment.job_post.publish') }}</x-ui.button>
                    @endcan
                    <p class="text-xs {{ $muted }}">{{ __('hr_recruitment.application_form.safety_note') }}</p>
                </div>
            </x-ui.panel>

            <x-ui.panel :title="__('hr_recruitment.application_form.add_question')" :subtitle="__('hr_recruitment.application_form.add_question_help')" :clip="false">
                <div class="space-y-3 p-4">
                    @if ($library->isEmpty())
                        <p class="text-sm {{ $muted }}">{{ __('hr_recruitment.application_form.library_empty') }}</p>
                    @else
                        <x-ui.form-select wire:model="libraryField" :label="__('hr_recruitment.application_form.question_library')" :placeholder="__('hr_recruitment.application_form.choose')" wrap-labels>
                            @foreach ($library as $key => $definition)
                                <option value="{{ $key }}">{{ $definition['label'][$locale] }}</option>
                            @endforeach
                        </x-ui.form-select>
                        <x-ui.button size="sm" tone="secondary" icon="plus" wire:click="addLibraryField">{{ __('hr_recruitment.application_form.add_question') }}</x-ui.button>
                    @endif
                </div>
            </x-ui.panel>

            <x-ui.panel :title="__('hr_recruitment.application_form.custom_question')" :subtitle="__('hr_recruitment.application_form.custom_question_help')" :clip="false">
                <form wire:submit="addCustomField" class="space-y-3 p-4">
                    <x-ui.form-select wire:model.live="customType" :label="__('hr_recruitment.application_form.question_type')" required>
                        @foreach (Forms::TYPE_CATALOG as $type => $definition)<option value="{{ $type }}">{{ $definition['label'][$locale] }}</option>@endforeach
                    </x-ui.form-select>
                    <x-ui.form-select wire:model="customSection" :label="__('hr_recruitment.application_form.section')" required>
                        @foreach (Forms::SECTION_CATALOG as $key => $section)<option value="{{ $key }}">{{ $section['label'][$locale] }}</option>@endforeach
                    </x-ui.form-select>
                    <x-ui.form-input wire:model="customLabelEn" :label="__('hr_recruitment.application_form.label_en')" :placeholder="__('hr_recruitment.application_form.label_en_placeholder')" dir="ltr" required />
                    <x-ui.form-input wire:model="customLabelAr" :label="__('hr_recruitment.application_form.label_ar')" :placeholder="__('hr_recruitment.application_form.label_ar_placeholder')" dir="rtl" required />
                    <x-ui.form-input wire:model="customHelpEn" :label="__('hr_recruitment.application_form.help_en')" dir="ltr" />
                    <x-ui.form-input wire:model="customHelpAr" :label="__('hr_recruitment.application_form.help_ar')" dir="rtl" />
                    @if (in_array($customType, ['select', 'radio'], true))
                        <x-ui.form-textarea wire:model="customOptionsEn" :label="__('hr_recruitment.application_form.options_en')" rows="3" dir="ltr" required />
                        <x-ui.form-textarea wire:model="customOptionsAr" :label="__('hr_recruitment.application_form.options_ar')" rows="3" dir="rtl" />
                    @endif
                    <flux:checkbox wire:model="customRequired" :label="__('hr_recruitment.application_form.required')" />
                    <x-ui.button type="submit" size="sm" tone="secondary" icon="plus">{{ __('hr_recruitment.application_form.add_custom_question') }}</x-ui.button>
                </form>
            </x-ui.panel>

            <x-ui.panel :title="__('hr_recruitment.application_form.reuse_form')" :subtitle="__('hr_recruitment.application_form.reuse_form_help')" :clip="false">
                <div class="space-y-3 p-4">
                    @if ($templates === [])
                        <p class="text-sm {{ $muted }}">{{ __('hr_recruitment.application_form.no_reusable_forms') }}</p>
                    @else
                        <x-ui.form-select wire:model="selectedTemplateVersionId" :label="__('hr_recruitment.application_form.choose_form_template')" :placeholder="__('hr_recruitment.application_form.choose')" wrap-labels>
                            @foreach ($templates as $template)
                                <option value="{{ $template['id'] }}">{{ $template['title'][$locale] }}@if ($template['city'][$locale] !== '') · {{ $template['city'][$locale] }}@endif · v{{ $template['version'] }}@if ($template['published_at']) · {{ $template['published_at'] }}@endif</option>
                            @endforeach
                        </x-ui.form-select>
                        <x-ui.button size="sm" tone="secondary" wire:click="applyPublishedTemplate" wire:confirm="{{ __('hr_recruitment.application_form.reuse_confirm') }}">{{ __('hr_recruitment.application_form.use_template') }}</x-ui.button>
                    @endif
                    <x-ui.button size="sm" tone="ghost" wire:click="applyReferenceTemplate" wire:confirm="{{ __('hr_recruitment.application_form.template_confirm') }}">{{ __('hr_recruitment.application_form.use_pdf_template') }}</x-ui.button>
                </div>
            </x-ui.panel>
        </aside>
    </div>

    <x-ui.confirm-modal wire:model="confirmPublish" tone="neutral" :title="__('hr_recruitment.job_post.publish')" :message="__('hr_recruitment.application_form.publish_confirm')" confirm="publish" :confirm-label="__('hr_recruitment.job_post.publish')" />
</div>
