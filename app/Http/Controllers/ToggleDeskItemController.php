<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\Desk\Panels;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ToggleDeskItemController extends Controller
{
    /**
     * "Add to desk" from anywhere (a page's header, a Desk tab, the Ask bar):
     * put an item on the person's Desk, or take it off when it is there.
     * The item is named by its id (a panel, page card or button), by a
     * page's route, or by a page's address (a Desk tab's), which resolve to
     * whatever stands for that page (Panels::itemForRoute); a record's page
     * stands in for its register. Only an item the person may see is accepted.
     */
    public function __invoke(Request $request, Panels $panels): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'item' => ['required_without_all:route,url', 'nullable', 'string', 'max:120'],
            'route' => ['required_without_all:item,url', 'nullable', 'string', 'max:120'],
            'url' => ['required_without_all:item,route', 'nullable', 'string', 'max:2000'],
            'placed' => ['sometimes', 'boolean'],
        ]);

        $available = $panels->availableTo($user);
        $route = filled($validated['route'] ?? null) ? (string) $validated['route'] : $this->routeAt((string) ($validated['url'] ?? ''));
        $item = filled($validated['item'] ?? null)
            ? $validated['item']
            : ($route === null ? null : ($panels->itemForRoute($user, $route) ?? $panels->itemForRoute($user, Str::beforeLast($route, '.').'.index')));

        abort_unless(is_string($item) && isset($available[$item]), 422);

        $layout = $panels->layoutFor($user);
        $isPlaced = in_array($item, array_column($layout, 'panel'), true);
        /* "placed" asks for a state rather than a flip, so a double click or a retry cannot undo itself. */
        $wanted = array_key_exists('placed', $validated) ? (bool) $validated['placed'] : ! $isPlaced;

        if ($wanted !== $isPlaced) {
            $layout = $wanted
                ? $panels->withItem($user, $layout, $item)
                : $panels->flush(array_values(array_filter($layout, fn (array $entry): bool => $entry['panel'] !== $item)));

            $user->forceFill(['desk_layout' => $layout])->save();
        }

        return response()->json([
            'item' => $item,
            'title' => $available[$item]['title'],
            'placed' => $wanted,
        ]);
    }

    /**
     * The route behind a same-site address, or null.
     */
    private function routeAt(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path) || $path === '' || (parse_url($url, PHP_URL_HOST) !== null && parse_url($url, PHP_URL_HOST) !== request()->getHost())) {
            return null;
        }

        try {
            return Route::getRoutes()->match(Request::create($path))->getName();
        } catch (HttpException) {
            return null;
        }
    }
}
