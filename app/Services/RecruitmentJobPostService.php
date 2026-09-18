<?php

namespace App\Services;

use App\Enums\RecruitmentEventType;
use App\Enums\RecruitmentRequestStatus;
use App\Models\HiringCampaign;
use App\Models\JobPost;
use App\Models\JobPostVersion;
use App\Models\RecruitmentRequestEvent;
use App\Models\User;
use DomainException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class RecruitmentJobPostService
{
    public const TEMPLATE = 'hope_center_hiring_v1';

    /**
     * Open the editor without overwriting an HR-authored draft. The first open
     * creates version 1 from the approved request; later opens prefer the
     * existing draft and otherwise show the currently published snapshot.
     *
     * @return array{job_post: JobPost, version: JobPostVersion, values: array{city: string, program: string, job_title: string}}
     */
    public function openEditor(HiringCampaign $campaign, User $actor): array
    {
        return DB::transaction(function () use ($campaign, $actor): array {
            $campaign = $this->loadSource($campaign);
            $this->assertApprovedSource($campaign);

            $jobPost = JobPost::query()->firstOrCreate(
                ['campaign_id' => $campaign->getKey()],
                [
                    'status' => 'draft',
                    'created_by_user_id' => $actor->getKey(),
                ],
            );

            $draft = $jobPost->versions()
                ->where('status', 'draft')
                ->latest('version_no')
                ->first();

            $version = $draft ?? $jobPost->currentVersion;

            if (! $version) {
                $values = $this->sourceValues($campaign);
                $version = $jobPost->versions()->create(
                    $this->versionAttributes(1, $values, 'draft'),
                );
            }

            return [
                'job_post' => $jobPost->fresh(),
                'version' => $version,
                'values' => $this->valuesFromVersion($version),
            ];
        });
    }

    /** @return array{city: string, program: string, job_title: string} */
    public function sourceValues(HiringCampaign $campaign): array
    {
        $campaign = $this->loadSource($campaign);
        $this->assertApprovedSource($campaign);

        $vacancy = $campaign->vacancy;
        $request = $vacancy->createdFromRequest;
        $detail = $request->latestVersion?->positionDetail;
        $position = $vacancy->position ?? $detail?->position;

        $city = $request->scopeCity;
        if ($city?->slug === 'SYR') {
            $city = $position?->city ?? $position?->center?->city ?? $city;
        }

        return $this->normalize([
            'city' => $city?->name_en
                ?? $position?->city?->name_en
                ?? $position?->center?->city?->name_en
                ?? '',
            'program' => $detail?->program?->name
                ?? $position?->program?->name
                ?? 'Christian Hope Center',
            'job_title' => $detail?->role_title_en
                ?? $detail?->position?->title_en
                ?? $position?->title_en
                ?? '',
        ]);
    }

    /**
     * @param  array{city: string, program: string, job_title: string}  $values
     */
    public function saveDraft(JobPost $jobPost, array $values): JobPostVersion
    {
        return DB::transaction(function () use ($jobPost, $values): JobPostVersion {
            $lockedPost = $this->lockPost($jobPost);
            $this->assertApprovedSource($this->loadSource($lockedPost->campaign));
            $values = $this->normalize($values);

            return $this->persistDraft($lockedPost, $values);
        });
    }

    /**
     * Publish an immutable version. Repeating the same publish request is
     * idempotent, so a double click cannot create two identical versions.
     *
     * @param  array{city: string, program: string, job_title: string}  $values
     */
    public function publish(JobPost $jobPost, array $values, User $actor): JobPostVersion
    {
        return DB::transaction(function () use ($jobPost, $values, $actor): JobPostVersion {
            $lockedPost = $this->lockPost($jobPost);
            $campaign = $this->loadSource($lockedPost->campaign);
            $this->assertApprovedSource($campaign);
            $values = $this->normalize($values);

            $current = $lockedPost->current_version_id
                ? JobPostVersion::query()->find($lockedPost->current_version_id)
                : null;
            $draft = $lockedPost->versions()
                ->where('status', 'draft')
                ->lockForUpdate()
                ->latest('version_no')
                ->first();

            if (! $draft && $current && $this->sameValues($current, $values)) {
                return $current;
            }

            $draft ??= $this->persistDraft($lockedPost, $values);
            $draft->forceFill([
                ...$this->versionAttributes($draft->version_no, $values, 'published'),
                'published_at' => now(),
            ])->save();

            $lockedPost->forceFill([
                'status' => 'published',
                'current_version_id' => $draft->getKey(),
            ])->save();

            $request = $campaign->vacancy->createdFromRequest;
            RecruitmentRequestEvent::query()->create([
                'recruitment_request_id' => $request->getKey(),
                'request_version_id' => $request->latestVersion?->getKey(),
                'event_type' => RecruitmentEventType::JobPostPublished,
                'actor_user_id' => $actor->getKey(),
                'actor_role' => $actor->myRole()?->name ?? 'unknown',
                'comment' => 'Job post version '.$draft->version_no.' published.',
                'meta' => [
                    'campaign_id' => $campaign->getKey(),
                    'job_post_id' => $lockedPost->getKey(),
                    'job_post_version_id' => $draft->getKey(),
                    'template' => self::TEMPLATE,
                ],
            ]);

            return $draft->fresh();
        });
    }

    /** @return array{city: string, program: string, job_title: string} */
    private function valuesFromVersion(JobPostVersion $version): array
    {
        return $this->normalize([
            'city' => Arr::get($version->public_payload_json, 'city', ''),
            'program' => Arr::get($version->public_payload_json, 'program', ''),
            'job_title' => Arr::get($version->public_payload_json, 'job_title', ''),
        ]);
    }

    /**
     * @param  array{city: string, program: string, job_title: string}  $values
     */
    private function persistDraft(JobPost $jobPost, array $values): JobPostVersion
    {
        $draft = $jobPost->versions()
            ->where('status', 'draft')
            ->lockForUpdate()
            ->latest('version_no')
            ->first();

        $current = $jobPost->current_version_id
            ? JobPostVersion::query()->find($jobPost->current_version_id)
            : null;

        if (! $draft && $current && $this->sameValues($current, $values)) {
            return $current;
        }

        if ($draft) {
            $draft->forceFill($this->versionAttributes($draft->version_no, $values, 'draft'))->save();

            return $draft->fresh();
        }

        $nextVersion = ((int) $jobPost->versions()->max('version_no')) + 1;

        return $jobPost->versions()->create(
            $this->versionAttributes($nextVersion, $values, 'draft'),
        );
    }

    private function lockPost(JobPost $jobPost): JobPost
    {
        return JobPost::query()
            ->with('campaign')
            ->lockForUpdate()
            ->findOrFail($jobPost->getKey());
    }

    private function loadSource(HiringCampaign $campaign): HiringCampaign
    {
        return $campaign->loadMissing([
            'vacancy.position.program',
            'vacancy.position.city',
            'vacancy.position.center.city',
            'vacancy.createdFromRequest.scopeCity',
            'vacancy.createdFromRequest.latestVersion.positionDetail.program',
            'vacancy.createdFromRequest.latestVersion.positionDetail.position',
        ]);
    }

    private function assertApprovedSource(HiringCampaign $campaign): void
    {
        $request = $campaign->vacancy?->createdFromRequest;

        if (! $request || ! in_array((int) $request->status, [
            RecruitmentRequestStatus::Approved,
            RecruitmentRequestStatus::Applied,
        ], true)) {
            throw new DomainException('A job post can only be generated from an approved recruitment request.');
        }
    }

    /**
     * @param  array{city: mixed, program: mixed, job_title: mixed}  $values
     * @return array{city: string, program: string, job_title: string}
     */
    private function normalize(array $values): array
    {
        return [
            'city' => mb_strtoupper(trim((string) ($values['city'] ?? ''))),
            'program' => mb_strtoupper(trim((string) ($values['program'] ?? ''))),
            'job_title' => trim((string) ($values['job_title'] ?? '')),
        ];
    }

    /**
     * @param  array{city: string, program: string, job_title: string}  $values
     * @return array<string, mixed>
     */
    private function versionAttributes(int $versionNumber, array $values, string $status): array
    {
        return [
            'version_no' => $versionNumber,
            'title_en' => "We're Hiring — {$values['job_title']}",
            'title_ar' => "مطلوب متطوع — {$values['job_title']}",
            'body_en' => null,
            'body_ar' => null,
            'public_payload_json' => [
                'template' => self::TEMPLATE,
                ...$values,
                'outputs' => [
                    'story' => ['width' => 2160, 'height' => 3840],
                    'post' => ['width' => 2160, 'height' => 2160],
                ],
            ],
            'status' => $status,
        ];
    }

    /** @param array{city: string, program: string, job_title: string} $values */
    private function sameValues(JobPostVersion $version, array $values): bool
    {
        return $this->valuesFromVersion($version) === $values;
    }
}
