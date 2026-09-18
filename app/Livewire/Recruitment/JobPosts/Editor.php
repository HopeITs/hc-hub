<?php

namespace App\Livewire\Recruitment\JobPosts;

use App\Models\HiringCampaign;
use App\Models\JobPost;
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

    public int $versionNumber = 1;

    public string $versionStatus = 'draft';

    public ?int $publishedVersionNumber = null;

    public ?string $notice = null;

    public string $noticeType = 'success';

    public function mount(HiringCampaign $campaign, RecruitmentJobPostService $service): void
    {
        $existing = $campaign->jobPost()->first();

        $existing
            ? Gate::authorize('manage', $existing)
            : Gate::authorize('create', [JobPost::class, $campaign]);

        try {
            $state = $service->openEditor($campaign, auth()->user());
        } catch (DomainException $exception) {
            abort(409, $exception->getMessage());
        }

        $this->campaign = $campaign;
        $this->jobPost = $state['job_post'];
        $this->fillValues($state['values']);
        $this->setVersionState($state['version']->version_no, $state['version']->status);
    }

    public function saveDraft(RecruitmentJobPostService $service): void
    {
        Gate::authorize('manage', $this->jobPost);
        $values = $this->validatedValues();
        $version = $service->saveDraft($this->jobPost, $values);

        $this->setVersionState($version->version_no, $version->status);
        $this->notice = $version->status === 'draft'
            ? __('recruitment.job_post.saved')
            : __('recruitment.job_post.no_changes');
        $this->noticeType = 'success';
    }

    public function publish(RecruitmentJobPostService $service): void
    {
        Gate::authorize('publish', $this->jobPost);
        $values = $this->validatedValues();
        $version = $service->publish($this->jobPost, $values, auth()->user());

        $this->jobPost->refresh();
        $this->setVersionState($version->version_no, $version->status);
        $this->notice = __('recruitment.job_post.published', ['version' => $version->version_no]);
        $this->noticeType = 'success';
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
        $this->notice = null;
    }

    public function render()
    {
        return view('livewire.recruitment.job-posts.editor');
    }

    /** @return array{city: string, program: string, job_title: string} */
    private function validatedValues(): array
    {
        $validated = $this->validate([
            'city' => ['required', 'string', 'max:80'],
            'program' => ['required', 'string', 'max:100'],
            'jobTitle' => ['required', 'string', 'max:120'],
        ]);

        return [
            'city' => $validated['city'],
            'program' => $validated['program'],
            'job_title' => $validated['jobTitle'],
        ];
    }

    /** @param array{city: string, program: string, job_title: string} $values */
    private function fillValues(array $values): void
    {
        $this->city = $values['city'];
        $this->program = $values['program'];
        $this->jobTitle = $values['job_title'];
    }

    private function setVersionState(int $versionNumber, string $status): void
    {
        $this->versionNumber = $versionNumber;
        $this->versionStatus = $status;
        $this->publishedVersionNumber = $this->jobPost->fresh()->currentVersion?->version_no;
    }
}
