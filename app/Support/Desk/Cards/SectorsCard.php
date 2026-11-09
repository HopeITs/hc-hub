<?php

declare(strict_types=1);

namespace App\Support\Desk\Cards;

use App\Models\Sector;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Sectors in card form: the active list and how many projects each tags.
 */
final class SectorsCard extends PageCard
{
    public function route(): string
    {
        return 'admin.sectors.index';
    }

    public function figures(User $user): array
    {
        $active = Sector::query()->active()->count();

        return [
            $this->figure(__('desk-workspace.card.figure.offered'), $active, 'good'),
            $this->figure(__('desk-workspace.card.figure.tagged_projects'), Sector::query()->has('projects')->count()),
            $this->figure(__('desk-workspace.card.figure.inactive'), Sector::query()->count() - $active),
        ];
    }

    protected function records(User $user, string $search): Collection
    {
        return Sector::query()->ordered()->withCount('projects')->get()->map(fn (Sector $sector): array => $this->row(
            'sc-'.$sector->id,
            $sector->localizedName(),
            trans_choice('desk-workspace.card.projects', (int) $sector->projects_count, ['count' => (int) $sector->projects_count]),
            $this->activeBadge($sector->is_active),
            route($this->route()),
        ));
    }
}
