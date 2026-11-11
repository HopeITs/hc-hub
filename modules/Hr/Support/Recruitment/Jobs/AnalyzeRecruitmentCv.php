<?php

namespace Modules\Hr\Support\Recruitment\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Hr\Actions\Recruitment\RecruitmentCvAnalysisService;
use Modules\Hr\Models\RecruitmentApplicantFile;

/** Reads one cleared CV. Re-running it simply replaces the stored reading. */
class AnalyzeRecruitmentCv implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 2;

    public int $timeout = 300;

    public function __construct(public readonly int $fileId) {}

    public function handle(RecruitmentCvAnalysisService $analyses): void
    {
        $file = RecruitmentApplicantFile::query()
            ->with('application.campaign.vacancy.createdFromRequest.latestVersion.positionDetail')
            ->find($this->fileId);

        if ($file !== null) {
            $analyses->analyze($file);
        }
    }
}
