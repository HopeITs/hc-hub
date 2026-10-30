<?php

namespace App\Http\Responses;

use App\Actions\Fortify\ActivateDefaultRole;
use App\Models\User;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    /**
     * Always land signed-in users on the dashboard (in the language they
     * signed in with), never on wherever they were before signing out.
     */
    public function toResponse($request): Response
    {
        if ($request->user() instanceof User) {
            ActivateDefaultRole::for($request->user());
        }

        if ($request->wantsJson()) {
            return response()->json(['two_factor' => false]);
        }

        $request->session()->forget('url.intended');

        return redirect()->route('dashboard');
    }
}
