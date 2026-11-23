<?php

declare(strict_types=1);

namespace Tests\Fixtures\Modules\Sample;

use App\Support\Modules\Contracts\MetricSource;

final class SampleMetrics implements MetricSource
{
    public const string Key = 'sample.count';

    public function metrics(): array
    {
        return [self::Key => ['label' => 'Sample count', 'resolve' => fn (?array $cities): int => 7]];
    }
}
