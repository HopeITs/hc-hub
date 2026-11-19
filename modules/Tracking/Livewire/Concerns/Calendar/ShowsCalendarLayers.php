<?php

declare(strict_types=1);

namespace Modules\Tracking\Livewire\Concerns\Calendar;

use App\Support\Modules\Contracts\CalendarLayerSource;
use App\Support\Modules\ModuleRegistry;
use Carbon\CarbonImmutable;
use Livewire\Attributes\Computed;

/**
 * The layers other modules add to calendar/index (leave, holidays…): which
 * the viewer is offered, which they switched off, and each layer's items on
 * the days on screen.
 *
 * The calendar knows no module: every layer comes from the module registry
 * and decides for itself who may see it. Switched-off layers travel in the
 * page's #[Url] hiddenLayers property; a key the viewer is not offered is
 * ignored, so the browser can never switch a layer on.
 *
 * Used by calendar/index only. The #[Computed] calendar() comes from
 * BuildsCalendarGrid.
 *
 * @property array<int, string> $hiddenLayers
 * @property-read array{month: CarbonImmutable, weeks: ?array<int, array<int, CarbonImmutable>>, days: ?array<int, CarbonImmutable>} $calendar  #[Computed] calendar()
 * @property-read list<array{key: string, label: string, icon: string, leads: bool, shown: bool}> $calendarLayers  #[Computed] calendarLayers()
 */
trait ShowsCalendarLayers
{
    /**
     * Switches one layer off or back on.
     */
    public function toggleLayer(string $key): void
    {
        if (! in_array($key, array_column($this->calendarLayers, 'key'), true)) {
            return;
        }

        $this->hiddenLayers = in_array($key, $this->hiddenLayers, true)
            ? array_values(array_diff($this->hiddenLayers, [$key]))
            : [...$this->hiddenLayers, $key];

        unset($this->calendarLayers, $this->layerItemsByDay);
    }

    /**
     * The layers the viewer is offered, in registration order.
     *
     * @return list<array{key: string, label: string, icon: string, leads: bool, shown: bool}>
     */
    #[Computed]
    public function calendarLayers(): array
    {
        return array_map(fn (CalendarLayerSource $layer): array => [
            'key' => $layer->key(),
            'label' => $layer->label(),
            'icon' => $layer->icon(),
            'leads' => $layer->leads(),
            'shown' => ! in_array($layer->key(), $this->hiddenLayers, true),
        ], $this->offeredLayers());
    }

    /**
     * Every shown layer's items per day on screen, leading layers first.
     *
     * @return array<string, list<array{layer: string, icon: string, leads: bool, id: string, title: string, context: string|null, href: string|null}>> keyed by Y-m-d
     */
    #[Computed]
    public function layerItemsByDay(): array
    {
        $user = auth()->user();
        $calendar = $this->calendar;
        $shown = $calendar['weeks'] === null ? ($calendar['days'] ?? []) : array_merge(...$calendar['weeks']);

        if ($user === null || $shown === []) {
            return [];
        }

        $days = [];

        foreach ($this->offeredLayers() as $layer) {
            if (in_array($layer->key(), $this->hiddenLayers, true)) {
                continue;
            }

            foreach ($layer->between($user, $shown[0], $shown[count($shown) - 1]) as $date => $items) {
                foreach ($items as $item) {
                    $days[$date][] = ['layer' => $layer->key(), 'icon' => $layer->icon(), 'leads' => $layer->leads()] + $item;
                }
            }
        }

        foreach ($days as $date => $items) {
            usort($items, fn (array $a, array $b): int => $b['leads'] <=> $a['leads']);
            $days[$date] = $items;
        }

        return $days;
    }

    /**
     * @return list<CalendarLayerSource>
     */
    private function offeredLayers(): array
    {
        $user = auth()->user();

        if ($user === null) {
            return [];
        }

        return array_values(array_filter(
            app(ModuleRegistry::class)->calendarLayerSources(),
            fn (CalendarLayerSource $layer): bool => $layer->allows($user),
        ));
    }
}
