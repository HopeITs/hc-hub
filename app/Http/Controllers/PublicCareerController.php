<?php

namespace App\Http\Controllers;

use App\Exceptions\PublicRecruitmentSubmissionException;
use App\Exceptions\PublicRecruitmentUnavailableException;
use App\Services\PublicRecruitmentLinkService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

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
            'submissionShellAccepted' => false,
        ]);
    }

    public function store(Request $request, string $token, PublicRecruitmentLinkService $service): Response
    {
        $locale = $this->setLocale($request);

        try {
            $page = $service->validateSubmissionShell($token, $request->all());
        } catch (PublicRecruitmentUnavailableException) {
            return $this->unavailable();
        } catch (PublicRecruitmentSubmissionException) {
            try {
                $page = $service->publicPage($token);
            } catch (PublicRecruitmentUnavailableException) {
                return $this->unavailable();
            }

            return response()->view('recruitment.careers.show', [
                ...$page,
                'locale' => $locale,
                'submissionError' => __('careers.generic_submission_error'),
                'submissionShellAccepted' => false,
            ], 422);
        }

        return response()->view('recruitment.careers.show', [
            ...$page,
            'locale' => $locale,
            'submissionError' => null,
            'submissionShellAccepted' => true,
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
