<?php

declare(strict_types=1);

namespace App\Support\Modules\Contracts;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Modules\Core\Models\User;

/**
 * A module's rows for the Desk agenda (Coming up), merged with the
 * platform's own by App\Livewire\Concerns\Desk\ReadsDeskAgenda. Registered
 * with ModuleRegistry::addDeskAgenda().
 */
interface DeskAgendaSource
{
    /**
     * The dated items between two days that involve the user.
     *
     * @return Collection<int, array{key: string, kind: string, date: CarbonImmutable, title: string, context: string, project: string|null, href: string}>
     */
    public function between(User $user, CarbonImmutable $from, CarbonImmutable $to): Collection;
}
