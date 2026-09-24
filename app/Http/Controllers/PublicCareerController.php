<?php

namespace App\Http\Controllers;

use App\Exceptions\PublicRecruitmentSubmissionException;
use App\Exceptions\PublicRecruitmentUnavailableException;
use App\Services\PublicRecruitmentApplicationService;
use App\Services\PublicRecruitmentLinkService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class PublicCareerController extends Controller
{
    public function show(Request $request, string $token, PublicRecruitmentLinkService $service): Response
    {
        $locale = $this->setLocale($request);

        try {
            $page = $service->publicPage($token);
        } catch (PublicRecruitmentUnavailableException) {
            return $this->unavailable();
        }

        return response()->view('recruitment.careers.show', [
            ...$page,
            'locale' => $locale,
            'submissionError' => null,
            'submissionErrors' => [],
            'submissionKey' => bin2hex(random_bytes(32)),
        ]);
    }

    public function store(
        Request $request,
        string $token,
        PublicRecruitmentLinkService $links,
        PublicRecruitmentApplicationService $applications,
    ): Response {
        $locale = $this->setLocale($request);

        try {
            $result = $applications->submit(
                $token,
                $request->all(),
                $request->ip(),
                $request->userAgent(),
            );
        } catch (PublicRecruitmentUnavailableException|PublicRecruitmentSubmissionException $exception) {
            $reason = $exception instanceof PublicRecruitmentSubmissionException ? $exception->reason : 'link_expired';
            $message = __("careers.errors.{$reason}");

            try {
                $page = $links->publicPage($token);
            } catch (PublicRecruitmentUnavailableException) {
                return response()->view('recruitment.careers.submission-error', [
                    'locale' => $locale,
                    'submissionError' => $message,
                ], 422);
            }

            return response()->view('recruitment.careers.show', [
                ...$page,
                'locale' => $locale,
                'submissionError' => $message,
                'submissionErrors' => $exception instanceof PublicRecruitmentSubmissionException
                    ? collect($exception->errors)->flatten()->unique()->values()->all()
                    : [],
                'submissionKey' => preg_match('/^[a-f0-9]{64}$/', (string) $request->input('submission_key')) === 1
                    ? Str::lower((string) $request->input('submission_key'))
                    : bin2hex(random_bytes(32)),
            ], 422);
        }

        return response()->view('recruitment.careers.submitted', [
            'locale' => $locale,
            'idempotent' => $result['idempotent'],
        ], 202);
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
        return response()->view('recruitment.careers.unavailable', status: 404);
    }
}
