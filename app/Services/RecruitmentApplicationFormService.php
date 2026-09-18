<?php

namespace App\Services;

use App\Enums\RecruitmentEventType;
use App\Enums\RecruitmentRequestStatus;
use App\Exceptions\StaleRecruitmentDraftException;
use App\Models\ApplicationForm;
use App\Models\ApplicationFormVersion;
use App\Models\HiringCampaign;
use App\Models\RecruitmentRequestEvent;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RecruitmentApplicationFormService
{
    /** @var array<string, array{type: string, required: bool, locked: bool, label: array{en: string, ar: string}}> */
    public const FIELD_CATALOG = [
        'full_name' => ['type' => 'text', 'required' => true, 'locked' => true, 'label' => ['en' => 'Full name', 'ar' => 'الاسم الكامل']],
        'email' => ['type' => 'email', 'required' => true, 'locked' => true, 'label' => ['en' => 'Email address', 'ar' => 'البريد الإلكتروني']],
        'phone' => ['type' => 'tel', 'required' => true, 'locked' => true, 'label' => ['en' => 'Phone number', 'ar' => 'رقم الهاتف']],
        'city' => ['type' => 'text', 'required' => true, 'locked' => false, 'label' => ['en' => 'City', 'ar' => 'المدينة']],
        'education' => ['type' => 'textarea', 'required' => false, 'locked' => false, 'label' => ['en' => 'Education', 'ar' => 'التعليم']],
        'experience' => ['type' => 'textarea', 'required' => false, 'locked' => false, 'label' => ['en' => 'Relevant experience', 'ar' => 'الخبرة ذات الصلة']],
        'motivation' => ['type' => 'textarea', 'required' => false, 'locked' => false, 'label' => ['en' => 'Why are you interested?', 'ar' => 'لماذا أنت مهتم؟']],
        'cv' => ['type' => 'file', 'required' => true, 'locked' => true, 'label' => ['en' => 'CV', 'ar' => 'السيرة الذاتية']],
    ];

    /** @return array{application_form: ApplicationForm, version: ApplicationFormVersion, fingerprint: string, fields: array<int, array<string, mixed>>, privacy_notice_version: string} */
    public function openEditor(HiringCampaign $campaign, User $actor): array
    {
        return DB::transaction(function () use ($campaign, $actor): array {
            $campaign = $this->loadSource($campaign);
            $this->assertApprovedSource($campaign);
            $form = ApplicationForm::query()->firstOrCreate(
                ['campaign_id' => $campaign->getKey()],
                ['status' => 'draft', 'created_by_user_id' => $actor->getKey()],
            );
            $version = $this->editableVersion($form);
            if (! $version) {
                $version = $form->versions()->create([
                    'version_no' => 1,
                    'fields_json' => $this->defaultFields(),
                    'privacy_notice_version' => 'v1',
                    'status' => 'draft',
                ]);
            }

            return $this->state($form->fresh(), $version);
        });
    }

    /** @param array<int, array<string, mixed>> $fields */
    public function saveDraft(ApplicationForm $form, array $fields, string $privacyNoticeVersion, int $expectedVersionId, string $expectedFingerprint): ApplicationFormVersion
    {
        return DB::transaction(function () use ($form, $fields, $privacyNoticeVersion, $expectedVersionId, $expectedFingerprint): ApplicationFormVersion {
            $lockedForm = $this->lockForm($form);
            $this->assertApprovedSource($this->loadSource($lockedForm->campaign));
            $editable = $this->editableVersion($lockedForm, true);
            $this->assertExpectedVersion($editable, $expectedVersionId, $expectedFingerprint);

            return $this->persistDraft($lockedForm, $this->normalizeFields($fields), $this->normalizePrivacyVersion($privacyNoticeVersion));
        });
    }

    /** @param array<int, array<string, mixed>> $fields */
    public function publish(ApplicationForm $form, array $fields, string $privacyNoticeVersion, User $actor, int $expectedVersionId, string $expectedFingerprint): ApplicationFormVersion
    {
        return DB::transaction(function () use ($form, $fields, $privacyNoticeVersion, $actor, $expectedVersionId, $expectedFingerprint): ApplicationFormVersion {
            $lockedForm = $this->lockForm($form);
            $campaign = $this->loadSource($lockedForm->campaign);
            $this->assertApprovedSource($campaign);
            $fields = $this->normalizeFields($fields);
            $privacyNoticeVersion = $this->normalizePrivacyVersion($privacyNoticeVersion);
            $editable = $this->editableVersion($lockedForm, true);

            if ($editable && $editable->status === 'published' && $this->sameDefinition($editable, $fields, $privacyNoticeVersion)) {
                return $editable;
            }

            $this->assertExpectedVersion($editable, $expectedVersionId, $expectedFingerprint);
            $draft = $this->persistDraft($lockedForm, $fields, $privacyNoticeVersion);
            $draft->forceFill(['status' => 'published', 'published_at' => now()])->save();
            $lockedForm->forceFill(['status' => 'published', 'current_version_id' => $draft->getKey()])->save();

            $request = $campaign->vacancy->createdFromRequest;
            RecruitmentRequestEvent::query()->create([
                'recruitment_request_id' => $request->getKey(),
                'request_version_id' => $request->latestVersion?->getKey(),
                'event_type' => RecruitmentEventType::FormVersionPublished,
                'actor_user_id' => $actor->getKey(),
                'actor_role' => $actor->myRole()?->name ?? 'unknown',
                'comment' => 'Application form version '.$draft->version_no.' published.',
                'meta' => [
                    'campaign_id' => $campaign->getKey(),
                    'application_form_id' => $lockedForm->getKey(),
                    'application_form_version_id' => $draft->getKey(),
                ],
            ]);

            return $draft->fresh();
        });
    }

    /** @return array{version: int, privacy_notice_version: string, fields: array<int, array<string, mixed>>} */
    public function publicSchema(HiringCampaign $campaign, ?int $versionId = null): array
    {
        $form = $campaign->applicationForm;
        $version = $versionId
            ? $form?->versions()->whereKey($versionId)->where('status', 'published')->first()
            : $form?->currentVersion;

        if (! $version || $version->status !== 'published') {
            throw new DomainException('This campaign does not have the requested published application form.');
        }

        return [
            'version' => $version->version_no,
            'privacy_notice_version' => (string) $version->privacy_notice_version,
            'fields' => array_map(fn (array $field): array => [
                'key' => $field['key'], 'type' => $field['type'], 'required' => $field['required'], 'label' => $field['label'],
            ], $this->normalizeFields($version->fields_json)),
        ];
    }

    /** @param array<string, mixed> $answers @return array<string, mixed> */
    public function validateSubmission(ApplicationFormVersion $version, array $answers): array
    {
        if ($version->status !== 'published') {
            throw new DomainException('Applications must use a published form version.');
        }

        $fields = $this->normalizeFields($version->fields_json);
        $keys = array_column($fields, 'key');
        $rules = ['answers' => ['required', 'array:'.implode(',', $keys)]];
        foreach ($fields as $field) {
            $rule = $field['required'] ? ['required'] : ['nullable'];
            $rule = match ($field['type']) {
                'email' => [...$rule, 'email:rfc', 'max:191'],
                'file' => [...$rule, 'file', 'mimes:pdf,doc,docx', 'max:10240'],
                'textarea' => [...$rule, 'string', 'max:5000'],
                default => [...$rule, 'string', 'max:255'],
            };
            $rules['answers.'.$field['key']] = $rule;
        }

        return Validator::make(['answers' => $answers], $rules)->validate()['answers'];
    }

    /** @return array<int, array<string, mixed>> */
    public function defaultFields(): array
    {
        return $this->normalizeFields(array_map(
            fn (string $key): array => ['key' => $key, ...self::FIELD_CATALOG[$key]],
            ['full_name', 'email', 'phone', 'city', 'experience', 'motivation', 'cv'],
        ));
    }

    public function fingerprint(ApplicationFormVersion $version): string
    {
        return hash('sha256', json_encode([
            'id' => $version->getKey(), 'fields' => $this->normalizeFields($version->fields_json),
            'privacy_notice_version' => $version->privacy_notice_version, 'status' => $version->status,
            'published_at' => $version->published_at?->toIso8601String(),
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /** @param array<int, array<string, mixed>> $fields @return array<int, array<string, mixed>> */
    public function normalizeFields(array $fields): array
    {
        $normalized = [];
        foreach ($fields as $field) {
            $key = (string) ($field['key'] ?? '');
            if (! isset(self::FIELD_CATALOG[$key]) || isset($normalized[$key])) {
                continue;
            }
            $catalog = self::FIELD_CATALOG[$key];
            $normalized[$key] = [
                'key' => $key,
                'type' => $catalog['type'],
                'required' => $catalog['locked'] ? $catalog['required'] : (bool) ($field['required'] ?? $catalog['required']),
                'locked' => $catalog['locked'],
                'label' => [
                    'en' => mb_substr(trim((string) ($field['label']['en'] ?? $catalog['label']['en'])), 0, 120),
                    'ar' => mb_substr(trim((string) ($field['label']['ar'] ?? $catalog['label']['ar'])), 0, 120),
                ],
            ];
        }

        foreach (self::FIELD_CATALOG as $key => $catalog) {
            if ($catalog['locked'] && ! isset($normalized[$key])) {
                $normalized[$key] = ['key' => $key, ...$catalog];
            }
        }

        return array_values($normalized);
    }

    /** @return array<string, mixed> */
    private function state(ApplicationForm $form, ApplicationFormVersion $version): array
    {
        return [
            'application_form' => $form, 'version' => $version, 'fingerprint' => $this->fingerprint($version),
            'fields' => $this->normalizeFields($version->fields_json),
            'privacy_notice_version' => (string) ($version->privacy_notice_version ?: 'v1'),
        ];
    }

    /** @param array<int, array<string, mixed>> $fields */
    private function persistDraft(ApplicationForm $form, array $fields, string $privacyVersion): ApplicationFormVersion
    {
        $draft = $form->versions()->where('status', 'draft')->lockForUpdate()->latest('version_no')->first();
        $current = $form->current_version_id ? ApplicationFormVersion::query()->find($form->current_version_id) : null;
        if (! $draft && $current && $this->sameDefinition($current, $fields, $privacyVersion)) {
            return $current;
        }
        if ($draft) {
            $draft->forceFill(['fields_json' => $fields, 'privacy_notice_version' => $privacyVersion])->save();

            return $draft->fresh();
        }

        return $form->versions()->create([
            'version_no' => ((int) $form->versions()->max('version_no')) + 1,
            'fields_json' => $fields, 'privacy_notice_version' => $privacyVersion, 'status' => 'draft',
        ]);
    }

    private function editableVersion(ApplicationForm $form, bool $lock = false): ?ApplicationFormVersion
    {
        $query = $form->versions()->where('status', 'draft')->latest('version_no');
        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->first() ?? $form->currentVersion;
    }

    private function assertExpectedVersion(?ApplicationFormVersion $version, int $expectedId, string $expectedFingerprint): void
    {
        if (! $version || (int) $version->getKey() !== $expectedId || ! hash_equals($this->fingerprint($version), $expectedFingerprint)) {
            throw new StaleRecruitmentDraftException;
        }
    }

    private function lockForm(ApplicationForm $form): ApplicationForm
    {
        return ApplicationForm::query()->with('campaign')->lockForUpdate()->findOrFail($form->getKey());
    }

    private function loadSource(HiringCampaign $campaign): HiringCampaign
    {
        return $campaign->loadMissing(['applicationForm.currentVersion', 'vacancy.createdFromRequest.latestVersion']);
    }

    private function assertApprovedSource(HiringCampaign $campaign): void
    {
        $request = $campaign->vacancy?->createdFromRequest;
        if (! $request || ! in_array((int) $request->status, [RecruitmentRequestStatus::Approved, RecruitmentRequestStatus::Applied], true)) {
            throw new DomainException('An application form can only be generated from an approved recruitment request.');
        }
    }

    /** @param array<int, array<string, mixed>> $fields */
    private function sameDefinition(ApplicationFormVersion $version, array $fields, string $privacyVersion): bool
    {
        return $this->normalizeFields($version->fields_json) === $fields
            && (string) $version->privacy_notice_version === $privacyVersion;
    }

    private function normalizePrivacyVersion(string $value): string
    {
        $value = mb_substr(trim($value), 0, 255);

        return $value === '' ? 'v1' : $value;
    }
}
