<?php

namespace App\Http\Responses;

use App\Actions\Fortify\ActivateDefaultRole;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Laravel\Passkeys\Contracts\PasskeyLoginResponse as PasskeyLoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

/**
 * A passkey sign-in ends like a password one (LoginResponse): the user's
 * default role is made the active one, and they land on the dashboard rather
 * than wherever they were before signing out.
 */
class PasskeyLoginResponse implements PasskeyLoginResponseContract
{
    public function toResponse($request): Response
    {
        if ($request->user() instanceof User) {
            ActivateDefaultRole::for($request->user());
        }

        $request->session()->forget('url.intended');

        if ($request->wantsJson()) {
            return new JsonResponse(['redirect' => route('dashboard')]);
        }

        return redirect()->route('dashboard');
    }
}
