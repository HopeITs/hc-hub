<?php

namespace App\Livewire\Recruitment\JobPosts;

use App\Exceptions\StaleRecruitmentDraftException;
use App\Models\HiringCampaign;
use App\Models\JobPost;
use App\Models\JobPostVersion;
use App\Services\RecruitmentJobPostService;
use DomainException;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Editor extends Component
{
    public HiringCampaign $campaign;

    public JobPost $jobPost;

    public string $city = '';

    public string $program = '';

    public string $jobTitle = '';

    public string $titleEn = '';

    public string $titleAr = '';

    public string $bodyEn = '';

    public string $bodyAr = '';

    public int $versionId;

    public string $versionFingerprint = '';

    public int $versionNumber = 1;

    public string $versionStatus = 'draft';

    public ?int $publishedVersionNumber = null;

    public ?int $publishedVersionId = null;

    public ?string $notice = null;

    public string $noticeType = 'success';

    public bool $hasConflict = false;

    public function mount(HiringCampaign $campaign, RecruitmentJobPostService $service): void
    {
        $existing = $campaign->jobPost()->first();
        $existing ? Gate::authorize('manage', $existing) : Gate::authorize('create', [JobPost::class, $campaign]);

        try {
            $this->applyState($service->openEditor($campaign, auth()->user()));
        } catch (DomainException $exception) {
            abort(409, $exception->getMessage());
        }
        $this->campaign = $campaign;
    }

    public function saveDraft(RecruitmentJobPostService $service): void
    {
        Gate::authorize('manage', $this->jobPost);
        try {
            $version = $service->saveDraft($this->jobPost, $this->validatedValues(), $this->versionId, $this->versionFingerprint);
            $this->syncVersion($version, $service);
            $this->success($version->status === 'draft' ? __('recruitment.job_post.saved') : __('recruitment.job_post.no_changes'));
        } catch (StaleRecruitmentDraftException) {
            $this->conflict();
        }
    }

    public function publish(RecruitmentJobPostService $service): void
    {
        Gate::authorize('publish', $this->jobPost);
        try {
            $version = $service->publish($this->jobPost, $this->validatedValues(), auth()->user(), $this->versionId, $this->versionFingerprint);
            $this->syncVersion($version, $service);
            $this->success(__('recruitment.job_post.published', ['version' => $version->version_no]));
        } catch (StaleRecruitmentDraftException) {
            $this->conflict();
        }
    }

    public function unpublish(RecruitmentJobPostService $service): void
    {
        Gate::authorize('publish', $this->jobPost);
        if (! $this->publishedVersionId) {
            return;
        }

        try {
            $version = $service->unpublish($this->jobPost, auth()->user(), $this->publishedVersionId);
            $this->fillValues($this->valuesFromVersion($version));
            $this->syncVersion($version, $service);
            $this->success(__('recruitment.job_post.unpublished'));
        } catch (StaleRecruitmentDraftException) {
            $this->conflict();
        }
    }

    public function reloadLatest(RecruitmentJobPostService $service): void
    {
        Gate::authorize('manage', $this->jobPost);
        $this->applyState($service->openEditor($this->campaign, auth()->user()));
        $this->hasConflict = false;
        $this->notice = __('recruitment.job_post.reloaded');
        $this->noticeType = 'info';
    }

    public function resetFromApprovedRequest(RecruitmentJobPostService $service): void
    {
        Gate::authorize('manage', $this->jobPost);
        $this->fillValues($service->sourceValues($this->campaign));
        $this->resetValidation();
        $this->notice = __('recruitment.job_post.reset_notice');
        $this->noticeType = 'info';
    }

    public function updated(): void
    {
        if (! $this->hasConflict) {
            $this->notice = null;
        }
    }

    public function render()
    {
        return view('livewire.recruitment.job-posts.editor');
    }

    /** @return array<string, string> */
    private function validatedValues(): array
    {
        $validated = $this->validate([
            'city' => ['required', 'string', 'max:80'], 'program' => ['required', 'string', 'max:100'],
            'jobTitle' => ['required', 'string', 'max:120'], 'titleEn' => ['required', 'string', 'max:255'],
            'titleAr' => ['required', 'string', 'max:255'], 'bodyEn' => ['nullable', 'string', 'max:10000'],
            'bodyAr' => ['nullable', 'string', 'max:10000'],
        ]);

        return [
            'city' => $validated['city'], 'program' => $validated['program'], 'job_title' => $validated['jobTitle'],
            'title_en' => $validated['titleEn'], 'title_ar' => $validated['titleAr'],
            'body_en' => $validated['bodyEn'] ?? '', 'body_ar' => $validated['bodyAr'] ?? '',
        ];
    }

    /** @param array<string, string> $values */
    private function fillValues(array $values): void
    {
        $this->city = $values['city'];
        $this->program = $values['program'];
        $this->jobTitle = $values['job_title'];
        $this->titleEn = $values['title_en'];
        $this->titleAr = $values['title_ar'];
        $this->bodyEn = $values['body_en'];
        $this->bodyAr = $values['body_ar'];
    }

    /** @param array<string, mixed> $state */
    private function applyState(array $state): void
    {
        $this->jobPost = $state['job_post'];
        $this->fillValues($state['values']);
        $this->versionFingerprint = $state['fingerprint'];
        $this->setVersionState($state['version']);
    }

    private function syncVersion(JobPostVersion $version, RecruitmentJobPostService $service): void
    {
        $this->jobPost->refresh();
        $this->versionFingerprint = $service->fingerprint($version);
        $this->setVersionState($version);
        $this->hasConflict = false;
    }

    private function setVersionState(JobPostVersion $version): void
    {
        $this->versionId = $version->getKey();
        $this->versionNumber = $version->version_no;
        $this->versionStatus = $version->status;
        $published = $this->jobPost->fresh()->currentVersion;
        $this->publishedVersionNumber = $published?->version_no;
        $this->publishedVersionId = $published?->getKey();
    }

    /** @return array<string, string> */
    private function valuesFromVersion(JobPostVersion $version): array
    {
        $payload = $version->public_payload_json;

        return [
            'city' => (string) ($payload['city'] ?? ''), 'program' => (string) ($payload['program'] ?? ''),
            'job_title' => (string) ($payload['job_title'] ?? ''), 'title_en' => (string) $version->title_en,
            'title_ar' => (string) $version->title_ar, 'body_en' => (string) $version->body_en, 'body_ar' => (string) $version->body_ar,
        ];
    }

    private function success(string $message): void
    {
        $this->notice = $message;
        $this->noticeType = 'success';
        $this->hasConflict = false;
    }

    private function conflict(): void
    {
        $this->notice = __('recruitment.job_post.conflict');
        $this->noticeType = 'error';
        $this->hasConflict = true;
    }
}
