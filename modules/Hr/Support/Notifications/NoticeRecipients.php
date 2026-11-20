<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Notifications;

use App\Notifications\Recipients\ModuleRecipients;

/**
 * The recipient map of HR's `leaves` and `recruitment` notification modules.
 *
 * HR's actions name the people themselves (the requester, the direct manager,
 * city HR, an approver) and hand that list to RecipientFilter, so no activity
 * fans out here: nobody is direct and no role watches. Registering the
 * modules is what puts them on the notification settings page, where a user
 * mutes them.
 */
final class NoticeRecipients extends ModuleRecipients {}
