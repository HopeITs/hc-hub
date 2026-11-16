<?php

declare(strict_types=1);

namespace App\Support\Modules\Contracts;

use Closure;
use Modules\Core\Models\User;

/**
 * A module's pages for App\Support\Desk\Destinations: the sidebar, the Ask
 * bar, Desk buttons and Quick access offer them by the same rule as the
 * platform's own pages. Registered with ModuleRegistry::addDestinations().
 */
interface DestinationSource
{
    /**
     * The module's pages in display order: route, label (translation key),
     * Desk icon, sidebar hero icon, sidebar group (one of
     * Destinations::Groups), the rule that opens it, whether that rule binds
     * administrators too, and the sidebar link it folds under, if any.
     *
     * @return list<array{route: string, label: string, icon: string, hero: string, group: string, allows: Closure(User): bool, bindsAdministrators: bool, under: string|null}>
     */
    public function pages(): array;
}
