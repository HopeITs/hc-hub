<?php

declare(strict_types=1);

namespace Modules\Hr\Support\CriticalPositions;

use Modules\Core\Models\User;
use Modules\Hr\Models\Position;

interface CandidateSource
{
    /** @return list<CandidateSuggestion> */
    public function suggest(Position $position, User $viewer, int $limit): array;
}
