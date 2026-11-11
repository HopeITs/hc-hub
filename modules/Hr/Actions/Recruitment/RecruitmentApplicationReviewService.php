<?php

namespace Modules\Hr\Actions\Recruitment;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Modules\Core\Models\User;
use Modules\Hr\Enums\RecruitmentEvaluationRecommendation;
use Modules\Hr\Models\RecruitmentApplication;
use Modules\Hr\Models\RecruitmentApplicationReview;
use Modules\Hr\Support\Recruitment\ActivityLogger;
use Modules\Hr\Support\Recruitment\RecruitmentConfig;

class RecruitmentApplicationReviewService
{
    public const AUDIT_LOG = 'recruitment-application-review';

    public function __construct(private readonly ActivityLogger $activity) {}

    /** @param array{recommendation?: mixed, pros?: mixed, cons?: mixed, notes?: mixed} $input */
    public function submit(User $reviewer, RecruitmentApplication $application, array $input, ?int $expectedVersion): RecruitmentApplicationReview
    {
        Gate::forUser($reviewer)->authorize('submit', [RecruitmentApplicationReview::class, $application]);
        $content = $this->validateContent($input);

        try {
            return DB::transaction(function () use ($reviewer, $application, $content, $expectedVersion): RecruitmentApplicationReview {
                $lockedApplication = RecruitmentApplication::query()->whereKey($application->getKey())->lockForUpdate()->firstOrFail();
                Gate::forUser($reviewer)->authorize('submit', [RecruitmentApplicationReview::class, $lockedApplication]);

                $review = RecruitmentApplicationReview::query()
                    ->where('application_id', $lockedApplication->getKey())
                    ->where('reviewer_user_id', $reviewer->getKey())
                    ->lockForUpdate()
                    ->first();

                if ($review === null) {
                    if ($expectedVersion !== null) {
                        throw $this->stale();
                    }

                    $review = RecruitmentApplicationReview::query()->create([
                        'application_id' => $lockedApplication->getKey(),
                        'reviewer_user_id' => $reviewer->getKey(),
                        ...$content,
                        'version' => 1,
                        'submitted_at' => now(),
                    ]);
                } else {
                    if ($expectedVersion !== $review->version) {
                        throw $this->stale();
                    }

                    $review->forceFill([...$content, 'version' => $review->version + 1])->save();
                }

                $this->activity->log(
                    'recruitment_application_review.saved',
                    subject: $review,
                    causer: $reviewer,
                    properties: [
                        'application_id' => $lockedApplication->getKey(),
                        'version' => $review->version,
                        'recommendation' => $review->recommendation,
                    ],
                    logName: self::AUDIT_LOG,
                );

                return $review;
            });
        } catch (UniqueConstraintViolationException) {
            throw $this->stale();
        }
    }

    /** @param array<string, mixed> $input @return array{recommendation: string, pros: ?string, cons: ?string, notes: ?string} */
    private function validateContent(array $input): array
    {
        $max = (int) RecruitmentConfig::get('recruitment.evaluations.max_text_length', 5000);
        $errors = [];
        $content = [];

        $recommendation = $input['recommendation'] ?? null;
        if (! in_array($recommendation, RecruitmentEvaluationRecommendation::values(), true)) {
            $errors['recommendation'] = __('hr_recruitment.applicant_ui.profile_reviews.errors.recommendation_required');
        }
        $content['recommendation'] = (string) $recommendation;

        foreach (['pros', 'cons', 'notes'] as $field) {
            $value = is_string($input[$field] ?? null) ? trim($input[$field]) : '';
            $value = $value === '' ? null : $value;

            if ($value !== null && mb_strlen($value) > $max) {
                $errors[$field] = __('hr_recruitment.applicant_ui.profile_reviews.errors.too_long', ['max' => $max]);
            }

            $content[$field] = $value;
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $content;
    }

    private function stale(): ValidationException
    {
        return ValidationException::withMessages([
            'review' => __('hr_recruitment.applicant_ui.profile_reviews.errors.stale'),
        ]);
    }
}
