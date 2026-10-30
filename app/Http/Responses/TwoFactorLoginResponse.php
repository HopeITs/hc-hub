<?php

namespace App\Http\Responses;

use App\Actions\Fortify\ActivateDefaultRole;
use App\Actions\Fortify\TrustedDevices;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class TwoFactorLoginResponse implements TwoFactorLoginResponseContract
{
    /**
     * Always land signed-in users on the dashboard, never on wherever they
     * were before signing out. Trusts this browser first when "don't ask
     * again on this device" was ticked.
     */
    public function toResponse($request): Response
    {
        $user = $request->user();

        if ($user instanceof User) {
            if ($request->boolean(TrustedDevices::RememberField)) {
                TrustedDevices::trust($request, $user);
            }

            ActivateDefaultRole::for($user);
        }

        if ($request->wantsJson()) {
            return new JsonResponse('', 204);
        }

        $request->session()->forget('url.intended');

        return redirect()->route('dashboard');
    }
}
