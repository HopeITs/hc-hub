<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Recruitment;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Hr\Actions\Recruitment\Privacy\ConfiguredRetentionPolicy;
use Modules\Hr\Actions\Recruitment\Privacy\RetentionPolicy;
use Modules\Hr\Actions\Recruitment\RecruitmentIndicatorRules;
use Modules\Hr\Actions\Recruitment\Scanning\ClamdScanner;
use Modules\Hr\Actions\Recruitment\Scanning\DisabledScanner;
use Modules\Hr\Actions\Recruitment\Scanning\RecruitmentFileScanner;
use Modules\Hr\Models\RecruitmentApplicant;
use Modules\Hr\Models\RecruitmentApplication;
use Modules\Hr\Models\RecruitmentCampaignInvite;
use Modules\Hr\Support\Recruitment\Observers\RecruitmentApplicantObserver;
use Modules\Hr\Support\Recruitment\Observers\RecruitmentApplicationObserver;
use Modules\Hr\Support\Recruitment\Observers\RecruitmentCampaignInviteObserver;

/**
 * What the old HR app registered for recruitment in its service provider:
 * container bindings, model observers and the careers rate limiters.
 *
 * Called from the recruitment route files so the area works without a change
 * to HrServiceProvider. It belongs in HrServiceProvider::register()/boot():
 * with cached routes the route files are not read, and nothing here runs.
 */
final class RecruitmentBootstrap
{
    private static bool $booted = false;

    public static function boot(): void
    {
        if (self::$booted) {
            return;
        }

        self::$booted = true;

        $app = app();

        $app->bind(RetentionPolicy::class, ConfiguredRetentionPolicy::class);
        $app->bind(RecruitmentFileScanner::class, fn (): RecruitmentFileScanner => RecruitmentConfig::get('recruitment-uploads.scanner.driver') === 'clamd'
            ? new ClamdScanner
            : new DisabledScanner);
        $app->singleton(RecruitmentIndicatorRules::class);

        RecruitmentApplicant::observe(RecruitmentApplicantObserver::class);
        RecruitmentApplication::observe(RecruitmentApplicationObserver::class);
        RecruitmentCampaignInvite::observe(RecruitmentCampaignInviteObserver::class);

        RateLimiter::for('careers-view', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));
        // Asking "is this address invited?" tells the asker, so it is the one
        // question on the public page that has to be slowed down.
        RateLimiter::for('careers-invitation', fn (Request $request) => [
            Limit::perMinute(6)->by($request->ip().'|'.hash('sha256', (string) $request->route('token'))),
            Limit::perHour(40)->by($request->ip()),
        ]);
        RateLimiter::for('careers-submit', fn (Request $request) => [
            Limit::perMinute(5)->by($request->ip().'|'.hash('sha256', (string) $request->route('token'))),
            Limit::perHour(30)->by($request->ip()),
        ]);
    }
}
