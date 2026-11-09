<?php

declare(strict_types=1);

namespace App\Support\Desk\Cards;

use App\Models\RiskLibrary;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * The risk library in card form: the ready-made risks, their categories and
 * how often projects picked each one.
 */
final class RiskLibraryCard extends PageCard
{
    public function route(): string
    {
        return 'admin.risk-library.index';
    }

    public function figures(User $user): array
    {
        return [
            $this->figure(__('desk-workspace.card.figure.offered'), RiskLibrary::query()->active()->count(), 'good'),
            $this->figure(__('desk-workspace.card.figure.categories'), RiskLibrary::query()->active()->whereNotNull('category')->distinct()->count('category')),
            $this->figure(__('desk-workspace.card.figure.picked'), RiskLibrary::query()->has('risks')->count()),
        ];
    }

    protected function records(User $user, string $search): Collection
    {
        $arabic = app()->getLocale() === 'ar';

        return RiskLibrary::query()->ordered()->withCount('risks')->get()->map(fn (RiskLibrary $entry): array => $this->row(
            'rl-'.$entry->id,
            $arabic ? $entry->title_ar : $entry->title_en,
            collect([
                $entry->category,
                trans_choice('desk-workspace.card.picked_times', (int) $entry->risks_count, ['count' => (int) $entry->risks_count]),
            ])->filter()->implode(' · '),
            $this->activeBadge($entry->is_active),
            route($this->route()),
        ));
    }
}
