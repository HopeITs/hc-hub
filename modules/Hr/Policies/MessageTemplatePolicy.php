<?php

declare(strict_types=1);

namespace Modules\Hr\Policies;

use Modules\Core\Models\User;
use Modules\Hr\Models\MessageTemplate;

/**
 * HR writes the email and WhatsApp templates (message-templates.manage);
 * whoever holds message-templates.use picks one on a person's profile.
 * A template holds no personal data until it is filled in for one person.
 */
class MessageTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('message-templates.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('message-templates.manage');
    }

    public function update(User $user, MessageTemplate $template): bool
    {
        return $user->can('message-templates.manage');
    }

    public function delete(User $user, MessageTemplate $template): bool
    {
        return $user->can('message-templates.manage');
    }

    /** Pick a template on a person's profile. */
    public function use(User $user): bool
    {
        return $user->can('message-templates.use');
    }
}
