<?php

declare(strict_types=1);

namespace App\Support\Modules\Contracts;

use Modules\Core\Models\User;

/**
 * A module's named, filtered lists for the shared searchable picker
 * (x-ui.form-select `picker-scope`, served by EmployeePickerOptionsController).
 * Each scope decides on the server which records it lists, what their value
 * is (an employee, user or position id) and who may read it. Registered with
 * ModuleRegistry::addPickerScopes().
 */
interface PickerScopeSource
{
    /**
     * Whether this module defines the named scope.
     */
    public function handles(string $scope): bool;

    /**
     * The only query parameters the scope accepts.
     *
     * @return list<string>
     */
    public function parameters(string $scope): array;

    /**
     * The scope's rows, or null when the user may not see this list.
     *
     * @param  array<string, string>  $parameters
     * @return list<array{value: string, label: string}>|null
     */
    public function options(string $scope, User $user, array $parameters): ?array;

    /**
     * The labels of already-picked values, for the server-rendered trigger.
     *
     * @param  list<string>  $values
     * @param  array<string, string>  $parameters
     * @return array<string, string>
     */
    public function labels(string $scope, array $values, array $parameters): array;
}
