<?php

declare(strict_types=1);

namespace Modules\Hr\Livewire\Concerns\Statistics;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Modules\Core\Models\City;
use Modules\Hr\Support\Desk\HrViewer;
use Modules\Hr\Support\Employees\HrAccess;

/**
 * Who may open an HR statistics page and which governorates its figures
 * cover: statistics.view (administrators pass), narrowed to the cities a
 * regional HR role looks after, as on the Desk's statistics page.
 */
trait ReadsStatisticsScope
{
    protected function authorizeStatistics(): void
    {
        $user = Auth::user();

        abort_unless($user !== null && ($user->isAdministrator() || $user->can('statistics.view')), 403);
    }

    /**
     * The viewer's governorates: null = every one, [] = none.
     *
     * @return list<int>|null
     */
    #[Computed]
    public function scope(): ?array
    {
        return HrViewer::for(Auth::user())->cityScope();
    }

    /**
     * The cities the viewer may pick, id => name.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function cityOptions(): array
    {
        $scope = $this->scope;

        return array_filter(City::options(withNational: false), fn (int $id): bool => $scope === null || in_array($id, $scope, true), ARRAY_FILTER_USE_KEY);
    }

    /**
     * The cities the figures cover: the picked city when it is one of the
     * viewer's, else the viewer's whole scope. A tampered city id never
     * widens the figures.
     *
     * @return list<int>|null
     */
    protected function citiesFor(string $city): ?array
    {
        if ($city !== '' && array_key_exists((int) $city, $this->cityOptions)) {
            return [(int) $city];
        }

        return $this->scope;
    }

    protected function canViewSalary(): bool
    {
        return HrAccess::canViewSalary(Auth::user());
    }
}
