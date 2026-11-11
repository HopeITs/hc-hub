<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Desk\Cards;

use Modules\Hr\Support\Desk\HrStatistics;

/**
 * Running contracts by contract type.
 */
final class ContractTypesCard extends HeadcountCard
{
    public function key(): string
    {
        return 'contract-types';
    }

    protected function split(HrStatistics $statistics, ?array $cities): array
    {
        return $statistics->byContractType($cities);
    }
}
