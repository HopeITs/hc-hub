<?php

declare(strict_types=1);

namespace Modules\Hr\Support\CriticalPositions;

use Modules\Hr\Models\Position;

final class NullCandidateAiRanker implements CandidateAiRanker
{
    public function rank(Position $position, array $suggestions): array
    {
        return $suggestions;
    }
}
