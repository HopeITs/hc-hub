<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Symfony\Component\HttpFoundation\Response;

class LogoutResponse implements LogoutResponseContract
{
    /**
     * Back to the sign-in page in the language the user was working in: the
     * logout route sits in the locale group, so the request's locale is still
     * set after Fortify cleared the session.
     */
    public function toResponse($request): Response
    {
        return $request->wantsJson()
            ? new JsonResponse('', 204)
            : redirect(LaravelLocalization::localizeUrl('/', app()->getLocale()));
    }
}
