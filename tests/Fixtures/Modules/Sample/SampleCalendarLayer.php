<?php

declare(strict_types=1);

namespace Tests\Fixtures\Modules\Sample;

use App\Support\Modules\Contracts\CalendarLayerSource;
use Carbon\CarbonImmutable;
use Modules\Core\Models\User;

final class SampleCalendarLayer implements CalendarLayerSource
{
    public function key(): string
    {
        return 'sample';
    }

    public function label(): string
    {
        return 'Sample layer';
    }

    public function icon(): string
    {
        return 'cal';
    }

    public function leads(): bool
    {
        return false;
    }

    public function allows(User $user): bool
    {
        return $user->can(SampleServiceProvider::Ability);
    }

    public function between(User $user, CarbonImmutable $from, CarbonImmutable $to): array
    {
        return [$from->toDateString() => [['id' => 'sample-1', 'title' => 'Sample day', 'context' => null, 'href' => null]]];
    }
}
