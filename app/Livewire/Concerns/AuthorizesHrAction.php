<?php

namespace App\Livewire\Concerns;

trait AuthorizesHrAction
{
    /**
     * Server-side guard for mutating Livewire actions. Blade hides the
     * buttons via @activerole, but actions remain callable by anyone who can
     * render the page — so every create/update/delete must re-check here.
     */
    protected function authorizeHrAction(): void
    {
        abort_unless(auth()->user()?->activeRoleIs('HR', 'super-admin'), 403);
    }
}
