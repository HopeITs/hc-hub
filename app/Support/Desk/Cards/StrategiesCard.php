<?php

declare(strict_types=1);

namespace App\Support\Desk\Cards;

use App\Models\Strategy;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Strategies in card form: the one in force now, the ones before and after
 * it, and how many programs each carries.
 */
final class StrategiesCard extends PageCard
{
    public function route(): string
    {
        return 'strategies.index';
    }

    public function figures(User $user): array
    {
        $year = now()->year;
        $current = Strategy::query()->where('start_year', '<=', $year)->where('end_year', '>=', $year)->orderByDesc('start_year')->first();

        return array_values(array_filter([
            $current !== null ? $this->figure(__('desk-workspace.card.figure.in_force'), $current->start_year.'–'.$current->end_year, 'good') : null,
            $this->figure(__('desk-workspace.card.figure.strategies'), Strategy::query()->count()),
            $current !== null ? $this->figure(__('desk-workspace.card.figure.programs'), $current->programs()->count(), 'active') : null,
        ]));
    }

    protected function records(User $user, string $search): Collection
    {
        $year = now()->year;

        return Strategy::query()->withCount('programs')->orderByDesc('start_year')->limit(self::RowLimit)->get()
            ->map(fn (Strategy $strategy): array => $this->row(
                'st-'.$strategy->id,
                $strategy->name,
                $strategy->start_year.'–'.$strategy->end_year.' · '.trans_choice('desk-workspace.card.programs', (int) $strategy->programs_count, ['count' => (int) $strategy->programs_count]),
                match (true) {
                    $strategy->start_year > $year => ['label' => (string) __('desk-workspace.card.upcoming'), 'tone' => 'active'],
                    $strategy->end_year < $year => ['label' => (string) __('desk-workspace.card.ended'), 'tone' => 'neutral'],
                    default => ['label' => (string) __('desk-workspace.card.in_force'), 'tone' => 'good'],
                },
                route('strategies.show', $strategy),
            ));
    }
}
