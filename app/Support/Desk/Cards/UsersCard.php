<?php

declare(strict_types=1);

namespace App\Support\Desk\Cards;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * The user directory in card form: who has an account and in which roles.
 */
final class UsersCard extends PageCard
{
    public function route(): string
    {
        return 'admin.users.index';
    }

    public function figures(User $user): array
    {
        return [
            $this->figure(__('desk-workspace.card.figure.accounts'), User::query()->count(), 'active'),
            $this->figure(__('desk-workspace.card.figure.roles'), Role::query()->count()),
            $this->figure(__('desk-workspace.card.figure.without_role'), User::query()->doesntHave('roles')->count(), 'warn'),
        ];
    }

    public function breakdown(User $user): array
    {
        $tones = ['active', 'good', 'warn', 'neutral'];

        return $this->parts(Role::query()->withCount('users')->orderByDesc('users_count')->limit(4)->get()
            ->values()
            ->map(fn (Role $role, int $i): array => ['label' => (string) $role->name, 'count' => (int) $role->users_count, 'tone' => $tones[$i] ?? 'neutral'])
            ->all());
    }

    protected function records(User $user, string $search): Collection
    {
        return User::query()->with('roles:id,name')->orderByName()->limit(self::RowLimit)->get()->map(fn (User $account): array => $this->row(
            'us-'.$account->id,
            $account->name !== '' ? $account->name : $account->email,
            $account->roles->pluck('name')->implode(' · ') ?: (string) __('desk-workspace.card.no_role'),
            null,
            route($this->route()),
        ));
    }
}
