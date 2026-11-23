<?php

declare(strict_types=1);

namespace Tests\Fixtures\Modules\Sample;

use App\Support\Modules\Contracts\DeskAgendaSource;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Modules\Core\Models\User;

final class SampleAgenda implements DeskAgendaSource
{
    public function between(User $user, CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        return collect([[
            'key' => 'sample-1',
            'kind' => 'sample',
            'date' => $from,
            'title' => 'Sample agenda row',
            'context' => 'Sample',
            'project' => null,
            'href' => '/sample',
        ]]);
    }
}
