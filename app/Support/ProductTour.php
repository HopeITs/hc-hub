<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Resolves the product tour for the page being rendered from config/tours.php.
 *
 * The lookup has to happen while the full page is rendered, not inside a
 * Livewire component: during a Livewire update request the current route is
 * `livewire.update`, so a component asking "which page am I on?" would get
 * the wrong answer on every re-render.
 */
class ProductTour
{
    /**
     * @return array{id: string, steps: list<array{target: ?string, placement: string, title: string, body: string}>}|null
     */
    public static function forRequest(Request $request): ?array
    {
        foreach (config('tours', []) as $id => $tour) {
            if (! $request->routeIs(...($tour['routes'] ?? []))) {
                continue;
            }

            $steps = [];

            foreach ($tour['steps'] ?? [] as $key => $step) {
                $steps[] = [
                    'target' => $step['target'] ?? null,
                    'placement' => $step['placement'] ?? 'bottom',
                    'title' => __("tour.{$id}.{$key}.title"),
                    'body' => __("tour.{$id}.{$key}.body"),
                ];
            }

            return $steps === [] ? null : ['id' => $id, 'steps' => $steps];
        }

        return null;
    }
}
