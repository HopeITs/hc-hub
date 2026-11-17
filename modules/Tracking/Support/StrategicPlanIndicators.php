<?php

declare(strict_types=1);

namespace Modules\Tracking\Support;

use Illuminate\Database\Eloquent\Collection;
use Modules\Tracking\Models\Indicator;

/**
 * The indicators a program's strategic plan may be measured by: those of the
 * program's own framework (not a proposal's), in framework order.
 */
final class StrategicPlanIndicators
{
    /**
     * @return Collection<int, Indicator>
     */
    public static function for(int $programId): Collection
    {
        return Indicator::query()
            ->select(['indicators.id', 'indicators.name', 'indicators.result_id', 'indicators.measure', 'indicators.unit', 'indicators.target'])
            ->whereHas('result', fn ($query) => $query->programFramework()->where('program_id', $programId))
            ->with('result:id,statement,type,program_id,proposal_id')
            ->orderBy('result_id')
            ->orderBy('id')
            ->get();
    }
}
