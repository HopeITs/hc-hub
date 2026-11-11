<?php

declare(strict_types=1);

namespace Modules\Tracking\Actions\MonthlyReports;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Tracking\Enums\MonthlyReportStatus;
use Modules\Tracking\Enums\ReopenRequestStatus;
use Modules\Tracking\Models\MonthlyReport;
use Modules\Tracking\Models\MonthlyReportReopenRequest;
use Modules\Tracking\Support\MonthlyReportDeciders;
use Modules\Tracking\Support\MonthlyReportGuard;

/**
 * Ask for a validated report to be reopened (contracts §13 Workflow, D-09a):
 * the preparer side, a reason of at least 10 characters, one pending request
 * per report. The deciders are told (`monthly_report.reopen_requested`); when
 * there is none the request stays pending and the General Manager is told
 * (`monthly_report.reopen_escalated`). Requests are never deleted.
 */
final class RequestMonthlyReportReopen
{
    public function handle(MonthlyReport $report, MonthlyReportStatus $expectedStatus, string $reason): MonthlyReportReopenRequest
    {
        $reason = trim($reason);

        if (mb_strlen($reason) < 10) {
            throw ValidationException::withMessages(['reason' => __('monthly_reports.reason_min')]);
        }

        return DB::transaction(function () use ($report, $expectedStatus, $reason): MonthlyReportReopenRequest {
            $locked = MonthlyReportGuard::lock($report, $expectedStatus, 'requestReopen');

            if ($locked->reopenRequests()->where('status', ReopenRequestStatus::Pending->value)->exists()) {
                throw ValidationException::withMessages(['status' => __('monthly_reports.reopen_pending')]);
            }

            $requesterId = (int) Auth::id();
            $request = $locked->reopenRequests()->create(['requested_by' => $requesterId, 'reason' => $reason]);
            $deciders = MonthlyReportDeciders::userIdsFor($locked, $requesterId);

            $locked->recordActivity(
                $deciders === [] ? 'monthly_report.reopen_escalated' : 'monthly_report.reopen_requested',
                properties: ['request_id' => $request->id, 'user_ids' => $deciders],
            );

            return $request;
        });
    }
}
