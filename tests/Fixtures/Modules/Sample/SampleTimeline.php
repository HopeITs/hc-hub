<?php

declare(strict_types=1);

namespace Tests\Fixtures\Modules\Sample;

use App\Support\Modules\Contracts\TimelineSource;
use Carbon\CarbonImmutable;
use Modules\Core\Models\User;

final class SampleTimeline implements TimelineSource
{
    public function key(): string
    {
        return 'sample';
    }

    public function label(): string
    {
        return 'Sample';
    }

    public function icon(): string
    {
        return 'check';
    }

    public function allows(User $viewer, int $employeeId): bool
    {
        return $viewer->can(SampleServiceProvider::Ability);
    }

    public function entries(User $viewer, int $employeeId): array
    {
        return [['date' => CarbonImmutable::parse('2026-01-15'), 'title' => 'Sample entry', 'detail' => null, 'href' => null]];
    }
}
