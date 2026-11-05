<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\Desk\Destinations;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ToggleNavPinController extends Controller
{
    public const int Limit = 8;

    /**
     * Pin a sidebar page to the top of the sidebar, or unpin it. Only a page
     * the person's own sidebar offers can be pinned.
     */
    public function __invoke(Request $request, Destinations $destinations): JsonResponse
    {
        $user = $request->user();
        $offered = collect($destinations->sidebarFor($user))->flatMap(fn (array $group): array => array_column($group['items'], 'route'))->all();

        $validated = $request->validate([
            'route' => ['required', 'string', Rule::in($offered)],
        ]);

        $pins = collect($user->nav_pins ?? [])->filter(fn (mixed $pin): bool => is_string($pin) && in_array($pin, $offered, true))->values();

        $pins = $pins->contains($validated['route'])
            ? $pins->reject(fn (string $pin): bool => $pin === $validated['route'])->values()
            : $pins->push($validated['route'])->take(self::Limit);

        $user->forceFill(['nav_pins' => $pins->all()])->saveQuietly();

        return response()->json(['pins' => $pins->all()]);
    }
}
