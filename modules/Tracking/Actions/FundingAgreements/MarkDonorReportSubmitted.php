<?php

declare(strict_types=1);

namespace Modules\Tracking\Actions\FundingAgreements;

use App\Enums\MediaCollection;
use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Modules\Core\Models\User;
use Modules\Tracking\Actions\Tasks\SyncReportTask;
use Modules\Tracking\Enums\ReportAudience;
use Modules\Tracking\Models\DonorReport;
use Modules\Tracking\Support\DonorReportFiles;
use Throwable;

/**
 * Record that a donor report went out (contracts §14.3, D-21), with the
 * delivered files attached in the same step.
 *
 * - Who: the responsible person or a `funding-agreements.update` holder
 *   (DonorReportPolicy::submit); no money permission is needed.
 * - The submission date is on or before today.
 * - A donor-facing report needs at least one `report_submission` file (the
 *   ones already attached count); an internal one may have none.
 * - The report's task (W-13) is closed in the same transaction.
 * - The report row is locked and re-checked: a second submission is refused
 *   and writes nothing. Files written before a failure are removed again.
 */
final class MarkDonorReportSubmitted
{
    /**
     * @param  list<UploadedFile>  $uploads
     *
     * @throws ValidationException submitted_in_future, file_required, already_submitted
     */
    public function handle(DonorReport $report, string $submittedOn, array $uploads, User $actor): DonorReport
    {
        Gate::forUser($actor)->authorize('submit', $report);

        DonorReportFiles::validateUploads($uploads, 'submissionFiles');

        if (validator(['submittedOn' => $submittedOn], ['submittedOn' => ['required', 'date', 'before_or_equal:today']])->fails()) {
            throw ValidationException::withMessages(['submittedOn' => __('donor_reports.submitted_in_future')]);
        }

        /** @var list<Media> $written */
        $written = [];

        try {
            return DB::transaction(function () use ($report, $submittedOn, $uploads, &$written): DonorReport {
                $locked = DonorReportFiles::lockUnsubmitted($report);

                foreach ($uploads as $upload) {
                    $written[] = DonorReportFiles::store($locked, $upload);
                }

                if ($locked->audience === ReportAudience::Donor && ! $locked->media()->where('collection', MediaCollection::ReportSubmission->value)->exists()) {
                    throw ValidationException::withMessages(['submissionFiles' => __('donor_reports.file_required')]);
                }

                $locked->update(['submitted_on' => $submittedOn]);

                app(SyncReportTask::class)->handle($locked);

                return $locked;
            });
        } catch (Throwable $exception) {
            DonorReportFiles::forget($written);

            throw $exception;
        }
    }
}
