<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Recruitment\Notifications;

/**
 * A recruitment notice addressed to the one person the record names: the
 * approver a step waits on, the requester, an interview participant. Muting
 * the recruitment module never hides it; only team-wide notices are muted.
 */
interface NamesItsRecipient {}
