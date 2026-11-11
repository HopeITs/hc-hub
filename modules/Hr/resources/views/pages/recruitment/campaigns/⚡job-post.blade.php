<?php

use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Modules\Core\Models\City;
use Modules\Core\Models\Program;
use Modules\Hr\Actions\Recruitment\RecruitmentJobPostService;
use Modules\Hr\Livewire\Concerns\Recruitment\AuthorizesRecruitment;
use Modules\Hr\Models\HiringCampaign;
use Modules\Hr\Models\JobPost;
use Modules\Hr\Models\JobPostVersion;
use Modules\Hr\Support\Recruitment\Exceptions\StaleRecruitmentDraftException;

/*
 * The job post of a campaign, drawn on the locked Hope Center template. The
 * service versions it: a draft is saved against the fingerprint it was
 * opened at, so two HR users cannot overwrite each other silently.
 */
new #[Layout('layouts::desk'), Title('hr_recruitment.hub.titles.job_post')] class extends Component {
    use AuthorizesRecruitment;

    #[Locked]
    public int $campaignId;

    #[Locked]
    public int $jobPostId;

    public string $city = '';

    public string $program = '';

    public string $jobTitle = '';

    public string $titleAr = '';

    public string $bodyEn = '';

    public string $bodyAr = '';

    /** @var list<string> */
    public array $cityOptions = [];

    /** @var list<string> */
    public array $programOptions = [];

    #[Locked]
    public int $versionId;

    #[Locked]
    public string $versionFingerprint = '';

    public int $versionNumber = 1;

    public string $versionStatus = 'draft';

    public ?int $publishedVersionNumber = null;

    #[Locked]
    public ?int $publishedVersionId = null;

    public bool $hasConflict = false;

    public bool $justPublished = false;

    public bool $confirmPublish = false;

    public bool $confirmUnpublish = false;

    public function mount(int $campaignId, RecruitmentJobPostService $service): void
    {
        $this->requirePermission('recruitment-job-posts.manage');
        $this->campaignId = $campaignId;
        $campaign = $this->campaign();
        $existing = $campaign->jobPost()->first();
        $existing ? Gate::authorize('manage', $existing) : Gate::authorize('create', [JobPost::class, $campaign]);

        try {
            $this->applyState($service->openEditor($campaign, $this->viewer()));
        } catch (DomainException $exception) {
            abort(409, $exception->getMessage());
        }

        $upper = fn ($names) => $names->map(fn ($name) => mb_strtoupper(trim((string) $name)))->filter()->unique()->values();
        $this->cityOptions = $upper(collect([$this->city])->merge(City::query()->where('is_active', true)->orderBy('name_en')->pluck('name_en')))->all();
        $this->programOptions = $upper(collect([$this->program])->merge(Program::query()->where('is_active', true)->orderBy('name')->pluck('name')))->all();
    }

    public function campaign(): HiringCampaign
    {
        $campaign = HiringCampaign::query()->visibleTo($this->viewer())
            ->with(['vacancy.position', 'vacancy.createdFromRequest']) // full rows: the services read them
            ->findOrFail($this->campaignId);
        Gate::authorize('view', $campaign);

        return $campaign;
    }

    public function jobPostModel(): JobPost
    {
        return $this->jobPost();
    }

    private function jobPost(): JobPost
    {
        return JobPost::query()->whereKey($this->jobPostId)->where('campaign_id', $this->campaignId)->firstOrFail();
    }

    public function updating(string $name): void
    {
        if (in_array($name, ['city', 'program', 'jobTitle'], true)) {
            $this->justPublished = false;
        }
    }

    public function saveDraft(RecruitmentJobPostService $service): void
    {
        $jobPost = $this->jobPost();
        Gate::authorize('manage', $jobPost);

        try {
            $version = $service->saveDraft($jobPost, $this->validatedValues(), $this->versionId, $this->versionFingerprint);
            $this->syncVersion($version, $service);
            $this->toast($version->status === 'draft' ? __('hr_recruitment.job_post.saved') : __('hr_recruitment.job_post.no_changes'));
        } catch (StaleRecruitmentDraftException) {
            $this->conflict();
        }
    }

    public function publish(RecruitmentJobPostService $service): void
    {
        $this->confirmPublish = false;
        $jobPost = $this->jobPost();
        Gate::authorize('publish', $jobPost);

        try {
            $version = $service->publish($jobPost, $this->validatedValues(), $this->viewer(), $this->versionId, $this->versionFingerprint);
            $this->syncVersion($version, $service);
            $this->toast(__('hr_recruitment.job_post.published', ['version' => $version->version_no]));
            $this->justPublished = true;
        } catch (StaleRecruitmentDraftException) {
            $this->conflict();
        }
    }

    public function unpublish(RecruitmentJobPostService $service): void
    {
        $this->confirmUnpublish = false;
        $jobPost = $this->jobPost();
        Gate::authorize('publish', $jobPost);

        if (! $this->publishedVersionId) {
            return;
        }

        try {
            $version = $service->unpublish($jobPost, $this->viewer(), $this->publishedVersionId);
            $payload = (array) $version->public_payload_json;
            $this->fillValues([
                'city' => (string) ($payload['city'] ?? ''), 'program' => (string) ($payload['program'] ?? ''),
                'job_title' => (string) ($payload['job_title'] ?? ''), 'title_ar' => (string) $version->title_ar,
                'body_en' => (string) $version->body_en, 'body_ar' => (string) $version->body_ar,
            ]);
            $this->syncVersion($version, $service);
            $this->toast(__('hr_recruitment.job_post.unpublished'));
        } catch (StaleRecruitmentDraftException) {
            $this->conflict();
        }
    }

    public function reloadLatest(RecruitmentJobPostService $service): void
    {
        Gate::authorize('manage', $this->jobPost());
        $this->applyState($service->openEditor($this->campaign(), $this->viewer()));
        $this->hasConflict = false;
        $this->toast(__('hr_recruitment.job_post.reloaded'));
    }

    public function resetFromApprovedRequest(RecruitmentJobPostService $service): void
    {
        Gate::authorize('manage', $this->jobPost());
        $this->fillValues($service->sourceValues($this->campaign()));
        $this->resetValidation();
        $this->toast(__('hr_recruitment.job_post.reset_notice'));
    }

    /** @return array{message: string, label: string, url: string}|null */
    public function nextStep(): ?array
    {
        if (! $this->justPublished) {
            return null;
        }

        $form = $this->campaign()->applicationForm()->with('currentVersion')->first();
        $formPublished = $form?->status === 'published' && $form?->currentVersion?->published_at !== null;

        return $formPublished
            ? ['message' => __('hr_recruitment.publishing.next_after_post_done'), 'label' => __('hr_recruitment.publishing.back'), 'url' => route('hr.recruitment.campaigns.show', ['campaignId' => $this->campaignId])]
            : ['message' => __('hr_recruitment.publishing.next_after_post'), 'label' => __('hr_recruitment.publishing.go_to_form'), 'url' => route('hr.recruitment.campaigns.application-form.edit', ['campaignId' => $this->campaignId])];
    }

    /** @return array<string, string> */
    private function validatedValues(): array
    {
        $validated = $this->validate([
            'city' => ['required', 'string', 'max:80'],
            'program' => ['required', 'string', 'max:100'],
            'jobTitle' => ['required', 'string', 'max:120'],
            'bodyEn' => ['nullable', 'string', 'max:10000'],
            'bodyAr' => ['nullable', 'string', 'max:10000'],
        ]);
        $arabic = trim(preg_replace('/^مطلوب\s+متطوع\s+[—-]\s*/u', '', $this->titleAr) ?? '');

        return [
            'city' => $validated['city'],
            'program' => $validated['program'],
            'job_title' => $validated['jobTitle'],
            'title_en' => "We're Hiring — {$validated['jobTitle']}",
            'title_ar' => 'مطلوب متطوع — '.($arabic !== '' ? $arabic : $validated['jobTitle']),
            'body_en' => $validated['bodyEn'] ?? '',
            'body_ar' => $validated['bodyAr'] ?? '',
        ];
    }

    /** @param  array<string, string>  $values */
    private function fillValues(array $values): void
    {
        $this->city = (string) ($values['city'] ?? '');
        $this->program = (string) ($values['program'] ?? '');
        $this->jobTitle = (string) ($values['job_title'] ?? '');
        $this->titleAr = (string) ($values['title_ar'] ?? '');
        $this->bodyEn = (string) ($values['body_en'] ?? '');
        $this->bodyAr = (string) ($values['body_ar'] ?? '');
    }

    /** @param  array<string, mixed>  $state */
    private function applyState(array $state): void
    {
        $this->jobPostId = (int) $state['job_post']->getKey();
        $this->fillValues($state['values']);
        $this->versionFingerprint = $state['fingerprint'];
        $this->setVersionState($state['version']);
    }

    private function syncVersion(JobPostVersion $version, RecruitmentJobPostService $service): void
    {
        $this->versionFingerprint = $service->fingerprint($version);
        $this->setVersionState($version);
        $this->hasConflict = false;
    }

    private function setVersionState(JobPostVersion $version): void
    {
        $this->versionId = (int) $version->getKey();
        $this->versionNumber = (int) $version->version_no;
        $this->versionStatus = (string) $version->status;
        $published = $this->jobPost()->currentVersion()->first();
        $this->publishedVersionNumber = $published?->version_no;
        $this->publishedVersionId = $published?->getKey();
    }

    private function conflict(): void
    {
        $this->hasConflict = true;
        $this->toast(__('hr_recruitment.job_post.conflict'), false);
    }
};
?>

@php
    $campaign = $this->campaign();
    $muted = 'text-secondary-medium dark:text-ink-muted';
@endphp

<div class="space-y-6"
    x-data="{ format: 'story', downloading: false, downloadMessage: '' }"
    data-font-regular="{{ route('hr.recruitment.assets', ['file' => 'somar-regular.ttf']) }}"
    data-font-bold="{{ route('hr.recruitment.assets', ['file' => 'somar-bold.ttf']) }}"
    data-font-black="{{ route('hr.recruitment.assets', ['file' => 'somar-black.ttf']) }}"
    data-job-title="{{ $jobTitle }}"
    data-exporting="{{ __('hr_recruitment.job_post.exporting') }}"
    data-downloaded="{{ __('hr_recruitment.job_post.downloaded') }}"
    data-export-failed="{{ __('hr_recruitment.job_post.export_failed') }}">

    <x-hr::recruitment.editor-context :campaign="$campaign" :title="__('hr_recruitment.job_post.title')"
        :version-number="$versionNumber" :version-status="$versionStatus" :published-version-number="$publishedVersionNumber" :next="$this->nextStep()" />

    @if ($hasConflict)
        <x-ui.panel :clip="false" class="flex flex-wrap items-center justify-between gap-3 p-4" role="alert">
            <p class="text-sm text-status-critical">{{ __('hr_recruitment.job_post.conflict') }}</p>
            <x-ui.button tone="secondary" wire:click="reloadLatest">{{ __('hr_recruitment.job_post.reload_latest') }}</x-ui.button>
        </x-ui.panel>
    @endif

    <div class="grid items-start gap-6 xl:grid-cols-[22rem_minmax(0,1fr)]">
        <x-ui.panel :title="__('hr_recruitment.job_post.title')" :subtitle="__('hr_recruitment.job_post.source_hint')" :clip="false">
            <div class="space-y-4 p-4">
                <x-ui.form-select wire:model.live="city" :label="__('hr_recruitment.job_post.city')" required>
                    @foreach ($cityOptions as $option)<option value="{{ $option }}">{{ $option }}</option>@endforeach
                </x-ui.form-select>
                <x-ui.form-select wire:model.live="program" :label="__('hr_recruitment.job_post.project')" required>
                    @foreach ($programOptions as $option)<option value="{{ $option }}">{{ $option }}</option>@endforeach
                </x-ui.form-select>
                <x-ui.form-input wire:model.live.debounce.300ms="jobTitle" :label="__('hr_recruitment.job_post.vacancy')" dir="ltr" required />
                <p class="text-xs {{ $muted }}">{{ __('hr_recruitment.job_post.role_details_moved') }}</p>
                <x-ui.button size="sm" tone="ghost" icon="arrow-path" wire:click="resetFromApprovedRequest">{{ __('hr_recruitment.job_post.reset') }}</x-ui.button>

                <div class="grid grid-cols-2 gap-1 rounded-md border border-panel-border p-1" role="group" aria-label="{{ __('hr_recruitment.job_post.preview') }}">
                    <button type="button" x-on:click="format = 'story'" x-bind:aria-pressed="format === 'story'" class="rounded px-3 py-1.5 text-sm font-semibold aria-pressed:bg-secondary-soft dark:aria-pressed:bg-surface-3">{{ __('hr_recruitment.job_post.story') }}</button>
                    <button type="button" x-on:click="format = 'post'" x-bind:aria-pressed="format === 'post'" class="rounded px-3 py-1.5 text-sm font-semibold aria-pressed:bg-secondary-soft dark:aria-pressed:bg-surface-3">{{ __('hr_recruitment.job_post.post') }}</button>
                </div>

                <div class="grid gap-2">
                    <x-ui.button tone="secondary" wire:click="saveDraft" wire:loading.attr="disabled" wire:target="saveDraft">{{ __('hr_recruitment.job_post.save_draft') }}</x-ui.button>
                    @can('publish', $this->jobPostModel())
                        <x-ui.button tone="primary" wire:click="$set('confirmPublish', true)">{{ __('hr_recruitment.job_post.publish') }}</x-ui.button>
                        @if ($publishedVersionId)
                            <x-ui.button tone="secondary" wire:click="$set('confirmUnpublish', true)">{{ __('hr_recruitment.job_post.unpublish') }}</x-ui.button>
                        @endif
                    @endcan
                    <x-ui.button tone="secondary" icon="arrow-down-tray" x-bind:disabled="downloading"
                        x-on:click="downloading = true; downloadMessage = $root.dataset.exporting; window.HopeCenterJobPostExporter.downloadBoth($root).then(() => downloadMessage = $root.dataset.downloaded).catch(() => downloadMessage = $root.dataset.exportFailed).finally(() => downloading = false)">
                        {{ __('hr_recruitment.job_post.download_both') }}
                    </x-ui.button>
                </div>
                <p class="text-xs {{ $muted }}" x-text="downloadMessage || @js(__('hr_recruitment.job_post.download_help'))" role="status"></p>
            </div>
        </x-ui.panel>

        <x-ui.panel :title="__('hr_recruitment.job_post.preview')" :subtitle="__('hr_recruitment.job_post.exact_template')">
            <div class="flex min-h-[32rem] items-center justify-center bg-secondary-soft/40 p-4 dark:bg-surface-2">
                <div x-show="format === 'story'" x-transition:enter="transition-opacity ease-out duration-150 motion-reduce:transition-none" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="flex w-full justify-center">
                    <x-hr::recruitment.job-post-svg id="hc-story-svg" format="story" :city="$city" :program="$program" :job-title="$jobTitle" class="block max-h-[72vh] w-auto max-w-full shadow-raised" />
                </div>
                <div x-show="format === 'post'" x-cloak x-transition:enter="transition-opacity ease-out duration-150 motion-reduce:transition-none" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="flex w-full justify-center">
                    <x-hr::recruitment.job-post-svg id="hc-post-svg" format="post" :city="$city" :program="$program" :job-title="$jobTitle" class="block max-h-[72vh] w-auto max-w-full shadow-raised" />
                </div>
            </div>
        </x-ui.panel>
    </div>

    <x-ui.confirm-modal wire:model="confirmPublish" tone="neutral" :title="__('hr_recruitment.job_post.publish')" :message="__('hr_recruitment.job_post.publish_confirm')" confirm="publish" :confirm-label="__('hr_recruitment.job_post.publish')" />
    <x-ui.confirm-modal wire:model="confirmUnpublish" :title="__('hr_recruitment.job_post.unpublish')" :message="__('hr_recruitment.job_post.unpublish_confirm')" confirm="unpublish" :confirm-label="__('hr_recruitment.job_post.unpublish')" />
</div>

@script
<script>
    // Renders the two SVG previews to PNG in the browser (no package): fonts
    // and artwork are inlined as data URLs so the canvas is not tainted.
    window.HopeCenterJobPostExporter ??= (() => {
        const cache = new Map();
        const asDataUrl = (url) => {
            if (! cache.has(url)) {
                cache.set(url, fetch(url, { credentials: 'same-origin' })
                    .then((response) => { if (! response.ok) { throw new Error('asset'); } return response.blob(); })
                    .then((blob) => new Promise((resolve, reject) => { const reader = new FileReader(); reader.onload = () => resolve(reader.result); reader.onerror = reject; reader.readAsDataURL(blob); })));
            }
            return cache.get(url);
        };
        const inline = async (source, root) => {
            const svg = source.cloneNode(true);
            await Promise.all([...svg.querySelectorAll('image')].map(async (image) => {
                const href = image.getAttribute('href');
                if (href && ! href.startsWith('data:')) { image.setAttribute('href', await asDataUrl(new URL(href, window.location.href).href)); }
            }));
            const [regular, bold, black] = await Promise.all([asDataUrl(root.dataset.fontRegular), asDataUrl(root.dataset.fontBold), asDataUrl(root.dataset.fontBlack)]);
            const ns = 'http://www.w3.org/2000/svg';
            const defs = document.createElementNS(ns, 'defs');
            const style = document.createElementNS(ns, 'style');
            style.textContent = [[400, regular], [700, bold], [900, black]].map(([weight, url]) => `@font-face{font-family:'SomarSans';font-weight:${weight};font-style:normal;src:url("${url}") format('truetype');}`).join('');
            defs.appendChild(style);
            svg.prepend(defs);
            return new XMLSerializer().serializeToString(svg);
        };
        const renderPng = async (svg, root) => {
            const url = URL.createObjectURL(new Blob([await inline(svg, root)], { type: 'image/svg+xml;charset=utf-8' }));
            try {
                const image = await new Promise((resolve, reject) => { const target = new Image(); target.onload = () => resolve(target); target.onerror = reject; target.src = url; });
                const canvas = document.createElement('canvas');
                canvas.width = Number(svg.dataset.exportWidth);
                canvas.height = Number(svg.dataset.exportHeight);
                canvas.getContext('2d').drawImage(image, 0, 0, canvas.width, canvas.height);
                return await new Promise((resolve, reject) => canvas.toBlob((blob) => blob ? resolve(blob) : reject(new Error('png')), 'image/png'));
            } finally {
                URL.revokeObjectURL(url);
            }
        };
        const download = (blob, name) => {
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = name;
            document.body.appendChild(link);
            link.click();
            link.remove();
            setTimeout(() => URL.revokeObjectURL(url), 1000);
        };
        const filePart = (value) => ((value || 'Hiring').trim().replace(/[<>:"/\\|?*\u0000-\u001F]/g, '').replace(/\s+/g, '_')) || 'Hiring';

        return {
            async downloadBoth(root) {
                const name = filePart(root.dataset.jobTitle);
                download(await renderPng(root.querySelector('#hc-story-svg'), root), `HopeCenter_${name}_story.png`);
                await new Promise((resolve) => setTimeout(resolve, 500));
                download(await renderPng(root.querySelector('#hc-post-svg'), root), `HopeCenter_${name}_post.png`);
            },
        };
    })();
</script>
@endscript
