<?php

namespace Modules\Hr\Support\Recruitment\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Modules\Hr\Actions\Recruitment\InternalRecruitmentApplicantService;
use Modules\Hr\Actions\Recruitment\PublicRecruitmentApplicationService;
use Modules\Hr\Actions\Recruitment\PublicRecruitmentLinkService;
use Modules\Hr\Actions\Recruitment\RecruitmentCampaignInviteService;
use Modules\Hr\Support\Recruitment\Exceptions\PublicRecruitmentSubmissionException;
use Modules\Hr\Support\Recruitment\Exceptions\PublicRecruitmentUnavailableException;
use Modules\Hr\Support\Recruitment\SecurityMonitor;

class PublicCareerController extends Controller
{
    public function show(Request $request, string $token, PublicRecruitmentLinkService $service, SecurityMonitor $monitor): Response
    {
        $locale = $this->setLocale($request);

        try {
            $page = $service->publicPage($token);
        } catch (PublicRecruitmentUnavailableException) {
            $monitor->tokenProbe($request, $token);

            return $this->unavailable();
        }

        return response()->view('hr::pages.recruitment.careers.show', [
            ...$page,
            'locale' => $locale,
            'internal' => $this->internalState($request, $token, $service, $locale),
            'invitation' => $this->invitationState($request, $token, $service),
            'submissionError' => null,
            'submissionErrors' => [],
            'submissionKey' => bin2hex(random_bytes(32)),
        ]);
    }

    /**
     * "Sign in to apply" on an internal campaign: HC-HR's own login, then
     * straight back to the vacancy, which now opens with their details.
     */
    public function signIn(Request $request, string $token, PublicRecruitmentLinkService $service, SecurityMonitor $monitor): RedirectResponse|Response
    {
        $locale = $this->setLocale($request);

        try {
            $service->publicPage($token);
        } catch (PublicRecruitmentUnavailableException) {
            $monitor->tokenProbe($request, $token);

            return $this->unavailable();
        }

        $back = route('hr.careers.show', ['token' => $token, 'lang' => $locale]);

        if ($request->user()) {
            return redirect()->to($back);
        }

        return redirect()->setIntendedUrl($back)
            ->to(route('login'));
    }

    /**
     * "Is this address invited?", asked before the form instead of after it.
     *
     * An invitation-only vacancy shows its form only to an address on the list.
     * The verified address is kept in the visitor's session, and read back
     * through the list again on every page view, so cancelling an invitation
     * closes the form even for somebody who already passed this step.
     */
    public function checkInvitation(
        Request $request,
        string $token,
        PublicRecruitmentLinkService $links,
        RecruitmentCampaignInviteService $invites,
        SecurityMonitor $monitor,
    ): RedirectResponse|Response {
        $locale = $this->setLocale($request);

        try {
            $page = $links->publicPage($token);
            $campaign = $links->campaignForPage($token);
        } catch (PublicRecruitmentUnavailableException) {
            $monitor->tokenProbe($request, $token);

            return $this->unavailable();
        }

        if (! $campaign->isInvitationOnly()) {
            return redirect()->route('hr.careers.show', ['token' => $token, 'lang' => $locale], 303);
        }

        $email = Str::lower(trim((string) $request->input('invitee_email')));

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $error = __('hr::pages.recruitment.careers.invitation.invalid_email');
        } elseif (! $invites->hasActiveInvite($campaign, $email)) {
            $error = __('hr::pages.recruitment.careers.invitation.not_listed');
        } else {
            $request->session()->put($this->invitationSessionKey($token), $email);

            return redirect()->route('hr.careers.show', ['token' => $token, 'lang' => $locale], 303);
        }

        return response()->view('hr::pages.recruitment.careers.show', [
            ...$page,
            'locale' => $locale,
            'internal' => null,
            'invitation' => ['state' => 'check', 'error' => $error],
            'submissionError' => null,
            'submissionErrors' => [],
            'submissionKey' => bin2hex(random_bytes(32)),
        ], 422);
    }

    public function submitted(Request $request, string $token): Response
    {
        $locale = $this->setLocale($request);

        return response()->view('hr::pages.recruitment.careers.submitted', [
            'locale' => $locale,
        ]);
    }

    public function store(
        Request $request,
        string $token,
        PublicRecruitmentLinkService $links,
        PublicRecruitmentApplicationService $applications,
        SecurityMonitor $monitor,
    ): RedirectResponse|Response {
        $locale = $this->setLocale($request);

        try {
            $result = $applications->submit(
                $token,
                $request->all(),
                $request->ip(),
                $request->userAgent(),
                $request->user(),
            );
        } catch (PublicRecruitmentUnavailableException|PublicRecruitmentSubmissionException $exception) {
            $reason = $exception instanceof PublicRecruitmentSubmissionException ? $exception->reason : 'link_expired';
            $message = __("hr_recruitment.careers.errors.{$reason}");

            $monitor->submissionFailed($request, $reason, $token);

            try {
                $page = $links->publicPage($token);
            } catch (PublicRecruitmentUnavailableException) {
                return response()->view('hr::pages.recruitment.careers.submission-error', [
                    'locale' => $locale,
                    'submissionError' => $message,
                ], 422);
            }

            return response()->view('hr::pages.recruitment.careers.show', [
                ...$page,
                'locale' => $locale,
                'internal' => $this->internalState($request, $token, $links, $locale),
                'invitation' => $this->invitationState($request, $token, $links),
                'submissionError' => $message,
                'submissionErrors' => $exception instanceof PublicRecruitmentSubmissionException
                    ? collect($exception->errors)->flatten()->unique()->values()->all()
                    : [],
                'submissionKey' => preg_match('/^[a-f0-9]{64}$/', (string) $request->input('submission_key')) === 1
                    ? Str::lower((string) $request->input('submission_key'))
                    : bin2hex(random_bytes(32)),
            ], 422);
        }

        return redirect()->route('hr.careers.submitted', [
            'token' => $token,
            'lang' => $locale,
        ], 303);
    }

    /**
     * What an internal campaign's page shows the visitor: a sign-in prompt,
     * a staff-only notice, "already applied", or the form with their details.
     * Null for any other campaign.
     *
     * @return array<string, mixed>|null
     */
    private function internalState(Request $request, string $token, PublicRecruitmentLinkService $links, string $locale): ?array
    {
        $campaign = $links->campaignForPage($token);
        if (! $campaign->isInternal()) {
            return null;
        }

        $user = $request->user();
        if (! $user) {
            return [
                'state' => 'sign_in',
                'sign_in_url' => route('hr.careers.sign-in', ['token' => $token, 'lang' => $locale]),
            ];
        }

        $staff = app(InternalRecruitmentApplicantService::class);
        $employee = $staff->employeeFor($user);
        if (! $employee) {
            return ['state' => 'not_staff'];
        }

        return [
            'state' => $staff->hasApplied($campaign, $user) ? 'applied' : 'ready',
            'employee' => $staff->profile($user, $employee, $locale),
            'prefill' => $staff->prefill($user, $employee, $locale),
        ];
    }

    /**
     * Where an invitation-only vacancy's visitor stands: still to say which
     * address they were invited with, or let through with it. Null for any
     * other campaign.
     *
     * @return array<string, mixed>|null
     */
    private function invitationState(Request $request, string $token, PublicRecruitmentLinkService $links): ?array
    {
        $campaign = $links->campaignForPage($token);
        if (! $campaign->isInvitationOnly()) {
            return null;
        }

        $email = $request->session()->get($this->invitationSessionKey($token));

        if (is_string($email) && app(RecruitmentCampaignInviteService::class)->hasActiveInvite($campaign, $email)) {
            return ['state' => 'ready', 'email' => $email];
        }

        $request->session()->forget($this->invitationSessionKey($token));

        return ['state' => 'check', 'error' => null];
    }

    /** Hashed, so the token never sits in the session store as it is in the URL. */
    private function invitationSessionKey(string $token): string
    {
        return 'hr::pages.recruitment.careers.invitee.'.hash('sha256', $token);
    }

    private function setLocale(Request $request): string
    {
        $locale = (string) ($request->input('locale') ?: $request->query('lang', 'en'));
        $locale = in_array($locale, ['en', 'ar'], true) ? $locale : 'en';
        app()->setLocale($locale);

        return $locale;
    }

    private function unavailable(): Response
    {
        return response()->view('hr::pages.recruitment.careers.unavailable', status: 404);
    }
}
