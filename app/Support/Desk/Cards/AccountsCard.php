<?php

declare(strict_types=1);

namespace App\Support\Desk\Cards;

use App\Models\Account;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Bank accounts in card form (accounts.view only, like the page): the open
 * accounts by currency, never a full account number.
 */
final class AccountsCard extends PageCard
{
    public function route(): string
    {
        return 'admin.accounts.index';
    }

    public function figures(User $user): array
    {
        return [
            $this->figure(__('desk-workspace.card.figure.open_accounts'), Account::query()->active()->count(), 'good'),
            $this->figure(__('desk-workspace.card.figure.currencies'), Account::query()->active()->distinct()->count('currency')),
            $this->figure(__('desk-workspace.card.figure.closed'), Account::query()->whereNotNull('closed_at')->count()),
        ];
    }

    protected function records(User $user, string $search): Collection
    {
        return Account::query()->orderByDesc('is_active')->orderBy('account_name')->limit(self::RowLimit)
            ->get(['id', 'account_name', 'bank_name', 'currency', 'account_number_last4', 'is_active'])
            ->map(fn (Account $account): array => $this->row(
                'acc-'.$account->id,
                $account->account_name,
                collect([$account->bank_name, filled($account->account_number_last4) ? '•••• '.$account->account_number_last4 : null])->filter()->implode(' · '),
                ['label' => $account->currency->value, 'tone' => $account->is_active ? 'active' : 'neutral'],
                route($this->route()),
            ));
    }
}
