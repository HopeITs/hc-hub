<?php

declare(strict_types=1);

namespace Tests\Fixtures\Modules\Sample;

use App\Support\Modules\Contracts\DeskPanelSource;
use Modules\Core\Models\User;

final class SamplePanels implements DeskPanelSource
{
    public const string Panel = 'sample-card';

    public function owns(string $id): bool
    {
        return str_starts_with($id, 'sample-');
    }

    public function component(string $id): string
    {
        return 'sample::desk.card';
    }

    public function kind(): string
    {
        return 'people';
    }

    public function definitions(): array
    {
        return [self::Panel => [
            'icon' => 'grid',
            'size' => 'M',
            'rows' => 4,
            'register' => null,
            'allows' => fn (User $user): bool => $user->can(SampleServiceProvider::Ability),
            'title' => 'Sample card',
            'description' => 'A card from the sample module',
            'locked' => 'Locked',
            'kind' => $this->kind(),
        ]];
    }

    public function routePanels(): array
    {
        return ['sample.index' => self::Panel];
    }

    public function roleLayouts(): array
    {
        return ['sample-role' => [[self::Panel, 'M']]];
    }
}
