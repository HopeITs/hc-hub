<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Search;

use App\Support\Like;
use App\Support\Modules\Contracts\SearchSource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Gate;
use Modules\Core\Models\City;
use Modules\Core\Models\User;
use Modules\Hr\Models\Position;

/**
 * Positions in the Ask bar: found by title in either language, through the
 * scope the team chart lists (Position::visible()), for whoever may view
 * positions. A row opens the team chart; the free seats are in its context.
 */
final class PositionSearchSource implements SearchSource
{
    public function key(): string
    {
        return 'positions';
    }

    public function label(): string
    {
        return __('hr_search.positions.label');
    }

    public function icon(): string
    {
        return 'flag';
    }

    public function words(): array
    {
        return ['position', 'positions', 'job', 'vacancy', 'منصب', 'مناصب', 'وظيفة', 'شاغر'];
    }

    public function allows(User $user): bool
    {
        return Gate::forUser($user)->allows('viewAny', Position::class);
    }

    public function search(User $user, string $term, int $limit): array
    {
        if (trim($term) === '' || $limit < 1) {
            return [];
        }

        return Position::query()
            ->visible()
            ->select(['id', 'title_en', 'title_ar', 'city_id', 'max_emp', 'exist_emp'])
            ->where(Like::everyWord($term, fn (Builder|QueryBuilder $fields, string $word) => $fields
                ->where(Like::word('title_en', $word))
                ->orWhere(Like::word('title_ar', $word)), acrossLayouts: false))
            ->orderBy('title_en')
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->map(function (Position $position): array {
                $free = max(0, (int) $position->max_emp - (int) $position->exist_emp);

                return [
                    'title' => (string) $position->title,
                    'context' => implode(' · ', array_filter([
                        City::nameOf($position->city_id),
                        $free > 0 ? trans_choice('hr_search.positions.free_seats', $free, ['count' => $free]) : __('hr_search.positions.full'),
                    ], fn (?string $part): bool => filled($part))),
                    'href' => route('hr.positions.team'),
                ];
            })
            ->all();
    }
}
