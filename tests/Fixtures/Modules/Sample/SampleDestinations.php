<?php

declare(strict_types=1);

namespace Tests\Fixtures\Modules\Sample;

use App\Support\Modules\Contracts\DestinationSource;
use Modules\Core\Models\User;

final class SampleDestinations implements DestinationSource
{
    public function pages(): array
    {
        return [[
            'route' => 'sample.index',
            'label' => 'Sample module',
            'icon' => 'grid',
            'hero' => 'nav-layout-dashboard',
            'group' => 'workspace',
            'allows' => fn (User $user): bool => $user->can(SampleServiceProvider::Ability),
            'bindsAdministrators' => false,
            'under' => null,
        ]];
    }
}
