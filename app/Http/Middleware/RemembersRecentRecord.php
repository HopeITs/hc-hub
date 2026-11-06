<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps the last records a person opened (a project, a monthly report, a
 * funding agreement) on users.nav_recent, newest first, for the sidebar's
 * Recent list. Only a successful page load counts, never a Livewire update.
 */
class RemembersRecentRecord
{
    public const int Limit = 5;

    /**
     * The record pages that count, with the route parameter holding the record and its kind.
     *
     * @var array<string, array{parameter: string, kind: string}>
     */
    public const array Pages = [
        'projects.show' => ['parameter' => 'project', 'kind' => 'project'],
        'monthly-reports.show' => ['parameter' => 'monthlyReport', 'kind' => 'monthly_report'],
        'funding-agreements.show' => ['parameter' => 'donorProposal', 'kind' => 'agreement'],
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $page = self::Pages[$request->route()?->getName() ?? ''] ?? null;
        $user = $request->user();

        if ($page === null || $user === null || ! $request->isMethod('GET') || $response->getStatusCode() !== 200) {
            return $response;
        }

        $record = $request->route()->parameter($page['parameter']);

        if (! $record instanceof Model || ! method_exists($record, 'activityLabel')) {
            return $response;
        }

        $routeName = (string) $request->route()->getName();
        $key = (string) $record->getRouteKey();
        $entry = ['route' => $routeName, 'key' => $key, 'label' => $record->activityLabel(), 'kind' => $page['kind']];

        $recent = collect($user->nav_recent ?? [])
            ->reject(fn (mixed $item): bool => ! is_array($item) || (($item['route'] ?? null) === $routeName && ($item['key'] ?? null) === $key))
            ->prepend($entry)
            ->take(self::Limit)
            ->values()
            ->all();

        if ($recent !== $user->nav_recent) {
            $user->forceFill(['nav_recent' => $recent])->saveQuietly();
        }

        return $response;
    }
}
