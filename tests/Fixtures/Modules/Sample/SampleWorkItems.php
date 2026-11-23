<?php

declare(strict_types=1);

namespace Tests\Fixtures\Modules\Sample;

use App\Support\Modules\Contracts\WorkItemSource;
use Modules\Core\Models\User;

final class SampleWorkItems implements WorkItemSource
{
    public function key(): string
    {
        return 'sample';
    }

    public function label(): string
    {
        return 'Sample work';
    }

    public function allows(User $user): bool
    {
        return $user->can(SampleServiceProvider::Ability);
    }

    public function href(User $user): ?string
    {
        return '/sample';
    }

    public function count(User $user): int
    {
        return 1;
    }

    public function items(User $user): array
    {
        return [['ref' => 'sample-1', 'title' => 'Sample item', 'context' => 'Sample', 'href' => '/sample', 'since' => null, 'urgent' => false]];
    }
}
