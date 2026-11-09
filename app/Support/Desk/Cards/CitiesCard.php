<?php

declare(strict_types=1);

namespace App\Support\Desk\Cards;

use App\Models\HrCity;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Cities in card form: where the work happens, busiest city first.
 */
final class CitiesCard extends PageCard
{
    public function route(): string
    {
        return 'admin.cities.index';
    }

    public function figures(User $user): array
    {
        $projects = $this->projectsByCity();

        return [
            $this->figure(__('desk-workspace.card.figure.active_cities'), HrCity::query()->where('is_active', true)->count(), 'good'),
            $this->figure(__('desk-workspace.card.figure.cities_with_work'), $projects->filter()->count()),
            $this->figure(__('desk-workspace.card.figure.projects'), $projects->sum()),
        ];
    }

    protected function records(User $user, string $search): Collection
    {
        $projects = $this->projectsByCity();

        return HrCity::query()->select(['id', 'name_en', 'name_ar', 'is_active'])->orderBy(HrCity::nameColumn())->get()
            ->sortByDesc(fn (HrCity $city): int => (int) ($projects[$city->id] ?? 0))
            ->map(fn (HrCity $city): array => $this->row(
                'ct-'.$city->id,
                (string) $city->name,
                trans_choice('desk-workspace.card.projects', (int) ($projects[$city->id] ?? 0), ['count' => (int) ($projects[$city->id] ?? 0)]),
                $this->activeBadge((bool) $city->is_active),
                route($this->route()),
            ))->values();
    }

    /**
     * @return Collection<int, int> city id => projects there
     */
    private function projectsByCity(): Collection
    {
        return Project::query()->toBase()->whereNotNull('city_id')->selectRaw('city_id, count(*) as total')->groupBy('city_id')->pluck('total', 'city_id')
            ->map(fn (mixed $total): int => (int) $total);
    }
}
