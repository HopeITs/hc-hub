<?php

declare(strict_types=1);

namespace App\Support\Modules\Contracts;

use Modules\Core\Models\User;

/**
 * A tab another module adds to an employee's profile, so HR shows what the
 * person does elsewhere without naming that module (roadmap 2.7, D10).
 * Registered with ModuleRegistry::addEmployeeTabs().
 */
interface EmployeeTabSource
{
    /**
     * The tab's key in the profile URL (?tab=), unique across modules.
     */
    public function key(): string;

    /**
     * The tab's label, in the reader's language.
     */
    public function label(): string;

    /**
     * Whether a login sees this tab on one employee's profile.
     */
    public function visibleTo(User $viewer, int $employeeId): bool;

    /**
     * The Livewire component that draws the tab; it receives `employeeId`.
     */
    public function component(): string;
}
