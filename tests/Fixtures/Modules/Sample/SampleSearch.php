<?php

declare(strict_types=1);

namespace Tests\Fixtures\Modules\Sample;

use App\Support\Modules\Contracts\SearchSource;
use Modules\Core\Models\User;

final class SampleSearch implements SearchSource
{
    public function key(): string
    {
        return 'sample';
    }

    public function label(): string
    {
        return 'Sample records';
    }

    public function icon(): string
    {
        return 'grid';
    }

    public function words(): array
    {
        return ['sample'];
    }

    public function allows(User $user): bool
    {
        return $user->can(SampleServiceProvider::Ability);
    }

    public function search(User $user, string $term, int $limit): array
    {
        return [['title' => 'Sample '.$term, 'context' => 'Sample', 'href' => '/sample']];
    }
}
