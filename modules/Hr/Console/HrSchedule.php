<?php

declare(strict_types=1);

namespace Modules\Hr\Console;

use App\Support\Modules\Contracts\ScheduleSource;
use Illuminate\Console\Scheduling\Schedule;
use Modules\Hr\Console\Journeys\DeactivateDepartedEmployees;
use Modules\Hr\Console\Journeys\SendContractExpirationWarnings;
use Modules\Hr\Console\Journeys\SendOnboardingStepReminders;
use Modules\Hr\Console\Journeys\SendOrientationReminders;
use Modules\Hr\Console\Journeys\TrackRenewalJourneys;
use Modules\Hr\Support\Recruitment\Console\ApplyDueRecruitmentRequests;
use Modules\Hr\Support\Recruitment\Console\ExpireRecruitmentJobOffers;
use Modules\Hr\Support\Recruitment\Console\RedispatchRecruitmentRejectionNotices;
use Modules\Hr\Support\Recruitment\Console\ScanRecruitmentFiles;
use Modules\Hr\Support\Recruitment\Console\SendRecruitmentInterviewReminders;
use Modules\Hr\Support\Recruitment\Console\SweepRecruitmentRetention;
use Modules\Hr\Support\Recruitment\Console\SyncRecruitmentCampaignStates;

/**
 * HR's background jobs, at the times the HR app ran them.
 */
final class HrSchedule implements ScheduleSource
{
    public function schedule(Schedule $schedule): void
    {
        // Evening contract runs, in this order: amendments take effect on their date, then ended
        // contracts go inactive and free their seat, then the ones that renew get a successor
        // (which so inherits the amended terms). The weekly check logs a broken amendment history.
        // Trials entering their last third get a review first, before tonight's contract sweeps.
        $schedule->command(StartProbationReviewsCommand::class)->dailyAt('21:20')->withoutOverlapping()->onOneServer();
        $schedule->command(ApplyContractAmendmentsCommand::class)->dailyAt('21:25')->withoutOverlapping()->onOneServer();
        $schedule->command(DeactivateExpiredContractsCommand::class)->dailyAt('21:30')->withoutOverlapping()->onOneServer();
        $schedule->command(ProcessContractAutoRenewalsCommand::class)->dailyAt('21:35')->withoutOverlapping()->onOneServer();
        $schedule->command(CheckContractAmendmentsCommand::class)->weeklyOn(0, '06:00')->onOneServer();
        // People who left over a month ago with no termination reason: HR of the city, once a month.
        $schedule->command(RemindMissingLeavingReasonsCommand::class)->monthlyOn(1, '08:30')->withoutOverlapping()->onOneServer();

        // Journeys (as the HR app ran them): expiry warnings and overdue orientations in the
        // morning; renewal journeys, then departed volunteers, after the evening contract runs.
        $schedule->command(SendContractExpirationWarnings::class)->dailyAt('08:00')->withoutOverlapping()->onOneServer();
        $schedule->command(SendOrientationReminders::class)->dailyAt('08:10')->withoutOverlapping()->onOneServer();
        // Weekly reminders to the owner of an overdue account, electronics, card or manager-session step.
        $schedule->command(SendOnboardingStepReminders::class)->dailyAt('08:12')->withoutOverlapping()->onOneServer();
        $schedule->command(TrackRenewalJourneys::class)->dailyAt('21:45')->withoutOverlapping()->onOneServer();
        $schedule->command(DeactivateDepartedEmployees::class)->dailyAt('21:50')->withoutOverlapping()->onOneServer();

        // One reminder per leave request still waiting for its decision.
        $schedule->command(SendLeaveDecisionRemindersCommand::class)->dailyAt('08:05')->withoutOverlapping()->onOneServer();

        // The first days of a month: managers to approve last month's timesheets, HR to lock them.
        $schedule->command(SendAttendanceRemindersCommand::class)->dailyAt('08:15')->withoutOverlapping()->onOneServer();

        // People whose contract just started join the mail lists their rules match (pending, for the control panel).
        $schedule->command(QueueMailListStartersCommand::class)->dailyAt('08:20')->withoutOverlapping()->onOneServer();

        // A birthday on a weekend or holiday in the coming days asks the volunteer, once, to pick another day.
        $schedule->command(NotifyBirthdayConflictsCommand::class)->dailyAt('08:25')->withoutOverlapping()->onOneServer();

        // Critical positions with no holder: HR of the city and the direct manager (once per episode).
        $schedule->command(NotifyCriticalPositionsCommand::class)->dailyAt('07:40')->withoutOverlapping()->onOneServer();

        // Recruitment (as the HR app ran it). Due position changes early, before the evening
        // contract runs read the seats; campaign windows every minute; the antivirus safety
        // net, offers past their answer date, interview reminders and rejection emails a down
        // queue held; retention dates and erasure requests at night.
        $schedule->command(ApplyDueRecruitmentRequests::class)->dailyAt('00:30')->onOneServer();
        $schedule->command(SyncRecruitmentCampaignStates::class)->everyMinute()->withoutOverlapping()->onOneServer();
        $schedule->command(ScanRecruitmentFiles::class)->everyFifteenMinutes()->withoutOverlapping()->onOneServer();
        $schedule->command(SendRecruitmentInterviewReminders::class)->everyFiveMinutes()->withoutOverlapping()->onOneServer();
        $schedule->command(ExpireRecruitmentJobOffers::class)->everyFifteenMinutes()->withoutOverlapping()->onOneServer();
        $schedule->command(RedispatchRecruitmentRejectionNotices::class)->everyFiveMinutes()->withoutOverlapping()->onOneServer();
        $schedule->command(SweepRecruitmentRetention::class)->dailyAt('02:15')->withoutOverlapping()->onOneServer();
    }
}
