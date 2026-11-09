<?php

declare(strict_types=1);

namespace App\Support\Desk\Cards;

use App\Models\DonorProposal;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Funding agreements in card form (funding-agreements.view only, like the
 * page): how many, the budget per currency, and the newest agreements.
 */
final class AgreementsCard extends PageCard
{
    public function route(): string
    {
        return 'funding-agreements.index';
    }

    public function figures(User $user): array
    {
        $figures = [
            $this->figure(__('desk-workspace.card.figure.agreements'), DonorProposal::query()->count(), 'active'),
            $this->figure(__('desk-workspace.card.figure.donors'), DonorProposal::query()->distinct()->count('donor_id')),
        ];

        foreach (DonorProposal::query()->toBase()->selectRaw('currency, sum(budget) as total')->groupBy('currency')->orderByDesc('total')->limit(2)->get() as $line) {
            $figures[] = $this->figure((string) __('desk-workspace.card.figure.budget', ['currency' => $line->currency]), (float) $line->total, 'good');
        }

        return $figures;
    }

    protected function records(User $user, string $search): Collection
    {
        return DonorProposal::query()->with(['donor:id,name', 'proposal:id,name'])->latest('id')->limit(self::RowLimit)->get()
            ->map(fn (DonorProposal $agreement): array => $this->row(
                'fa-'.$agreement->id,
                $agreement->proposal?->name ?? '—',
                collect([$agreement->donor?->name, $this->number((float) $agreement->budget).' '.$agreement->currency->value])->filter()->implode(' · '),
                null,
                route('funding-agreements.show', $agreement),
            ));
    }
}
