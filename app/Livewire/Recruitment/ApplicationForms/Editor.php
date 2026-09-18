<?php

namespace App\Livewire\Recruitment\ApplicationForms;

use App\Exceptions\StaleRecruitmentDraftException;
use App\Models\ApplicationForm;
use App\Models\ApplicationFormVersion;
use App\Models\HiringCampaign;
use App\Services\RecruitmentApplicationFormService;
use DomainException;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Editor extends Component
{
    public HiringCampaign $campaign;

    public ApplicationForm $applicationForm;

    /** @var array<int, array<string, mixed>> */
    public array $fields = [];

    public string $privacyNoticeVersion = 'v1';

    public int $versionId;

    public string $versionFingerprint = '';

    public int $versionNumber = 1;

    public string $versionStatus = 'draft';

    public ?int $publishedVersionNumber = null;

    public ?string $notice = null;

    public string $noticeType = 'success';

    public bool $hasConflict = false;

    public function mount(HiringCampaign $campaign, RecruitmentApplicationFormService $service): void
    {
        $existing = $campaign->applicationForm()->first();
        $existing ? Gate::authorize('manage', $existing) : Gate::authorize('create', [ApplicationForm::class, $campaign]);

        try {
            $this->applyState($service->openEditor($campaign, auth()->user()));
        } catch (DomainException $exception) {
            abort(409, $exception->getMessage());
        }
        $this->campaign = $campaign;
    }

    public function addField(string $key): void
    {
        Gate::authorize('manage', $this->applicationForm);
        if (! isset(RecruitmentApplicationFormService::FIELD_CATALOG[$key]) || collect($this->fields)->contains('key', $key)) {
            return;
        }
        $catalog = RecruitmentApplicationFormService::FIELD_CATALOG[$key];
        $this->fields[] = ['key' => $key, ...$catalog];
        $this->notice = null;
    }

    public function removeField(int $index): void
    {
        Gate::authorize('manage', $this->applicationForm);
        if (($this->fields[$index]['locked'] ?? true) === true) {
            return;
        }
        array_splice($this->fields, $index, 1);
        $this->notice = null;
    }

    public function moveField(int $index, int $direction): void
    {
        Gate::authorize('manage', $this->applicationForm);
        $target = $index + ($direction <=> 0);
        if (! isset($this->fields[$index], $this->fields[$target])) {
            return;
        }
        [$this->fields[$index], $this->fields[$target]] = [$this->fields[$target], $this->fields[$index]];
        $this->notice = null;
    }

    public function saveDraft(RecruitmentApplicationFormService $service): void
    {
        Gate::authorize('manage', $this->applicationForm);
        $this->validateEditor();
        try {
            $version = $service->saveDraft(
                $this->applicationForm, $this->fields, $this->privacyNoticeVersion, $this->versionId, $this->versionFingerprint,
            );
            $this->syncVersion($version, $service);
            $this->success(__('recruitment.application_form.saved'));
        } catch (StaleRecruitmentDraftException) {
            $this->conflict();
        }
    }

    public function publish(RecruitmentApplicationFormService $service): void
    {
        Gate::authorize('publish', $this->applicationForm);
        $this->validateEditor();
        try {
            $version = $service->publish(
                $this->applicationForm, $this->fields, $this->privacyNoticeVersion, auth()->user(), $this->versionId, $this->versionFingerprint,
            );
            $this->syncVersion($version, $service);
            $this->success(__('recruitment.application_form.published', ['version' => $version->version_no]));
        } catch (StaleRecruitmentDraftException) {
            $this->conflict();
        }
    }

    public function reloadLatest(RecruitmentApplicationFormService $service): void
    {
        Gate::authorize('manage', $this->applicationForm);
        $this->applyState($service->openEditor($this->campaign, auth()->user()));
        $this->hasConflict = false;
        $this->notice = __('recruitment.application_form.reloaded');
        $this->noticeType = 'info';
    }

    public function render()
    {
        $used = array_column($this->fields, 'key');

        return view('livewire.recruitment.application-forms.editor', [
            'availableFields' => collect(RecruitmentApplicationFormService::FIELD_CATALOG)->except($used),
        ]);
    }

    private function validateEditor(): void
    {
        $this->validate([
            'privacyNoticeVersion' => ['required', 'string', 'max:255'],
            'fields' => ['required', 'array', 'min:4'],
            'fields.*.label.en' => ['required', 'string', 'max:120'],
            'fields.*.label.ar' => ['required', 'string', 'max:120'],
            'fields.*.required' => ['boolean'],
        ]);
    }

    /** @param array<string, mixed> $state */
    private function applyState(array $state): void
    {
        $this->applicationForm = $state['application_form'];
        $this->fields = $state['fields'];
        $this->privacyNoticeVersion = $state['privacy_notice_version'];
        $this->versionFingerprint = $state['fingerprint'];
        $this->setVersionState($state['version']);
    }

    private function syncVersion(ApplicationFormVersion $version, RecruitmentApplicationFormService $service): void
    {
        $this->applicationForm->refresh();
        $this->fields = $service->normalizeFields($version->fields_json);
        $this->versionFingerprint = $service->fingerprint($version);
        $this->setVersionState($version);
        $this->hasConflict = false;
    }

    private function setVersionState(ApplicationFormVersion $version): void
    {
        $this->versionId = $version->getKey();
        $this->versionNumber = $version->version_no;
        $this->versionStatus = $version->status;
        $this->publishedVersionNumber = $this->applicationForm->fresh()->currentVersion?->version_no;
    }

    private function success(string $message): void
    {
        $this->notice = $message;
        $this->noticeType = 'success';
        $this->hasConflict = false;
    }

    private function conflict(): void
    {
        $this->notice = __('recruitment.application_form.conflict');
        $this->noticeType = 'error';
        $this->hasConflict = true;
    }
}
