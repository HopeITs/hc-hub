<?php

declare(strict_types=1);

namespace Modules\Tracking\Enums;

use App\Enums\Concerns\HasTranslatedLabel;

/**
 * Where a change request on an approved proposal stands (contracts.md §7,
 * Y-09, D-89a). A withdrawn request is `rejected` with the requester's note.
 */
enum ProposalChangeRequestStatus: string
{
    use HasTranslatedLabel;

    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
