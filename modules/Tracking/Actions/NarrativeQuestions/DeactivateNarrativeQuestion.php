<?php

declare(strict_types=1);

namespace Modules\Tracking\Actions\NarrativeQuestions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Modules\Tracking\Models\NarrativeQuestion;

/**
 * Stop a question being copied into new report templates. Templates that
 * already copied it keep their copy.
 */
final class DeactivateNarrativeQuestion
{
    /**
     * @throws ValidationException when the question is already inactive (someone else got there first)
     */
    public function handle(int $questionId): NarrativeQuestion
    {
        return DB::transaction(function () use ($questionId): NarrativeQuestion {
            $question = NarrativeQuestion::query()->lockForUpdate()->findOrFail($questionId);

            Gate::authorize('update', $question);

            if (! $question->is_active) {
                throw ValidationException::withMessages(['question' => __('narrative_questions.stale')]);
            }

            $question->forceFill(['is_active' => false])->save();

            return $question;
        });
    }
}
