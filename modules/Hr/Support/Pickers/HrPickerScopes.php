<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Pickers;

use App\Support\Modules\Contracts\PickerScopeSource;
use Illuminate\Support\Facades\Gate;
use Modules\Core\Models\City;
use Modules\Core\Models\User;
use Modules\Hr\Models\DefaultDeliverable;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\Folder;
use Modules\Hr\Models\Position;
use Modules\Hr\Support\Checklists\ChecklistPeople;

/**
 * HR's filtered picker lists. Each one answers only a viewer who may open
 * the form it fills, with the same policy check the page makes:
 *
 * - `hr.default-deliverers` (city): employee ids, the governorate's
 *   service-department staff; DefaultDeliverablePolicy::create for that city.
 * - `hr.archive-users` (folder): user ids, every login; FolderPolicy::share.
 * - `hr.position-managers` (position): position ids, the active positions
 *   but the one edited; PositionPolicy::update on it, else ::create.
 */
final class HrPickerScopes implements PickerScopeSource
{
    public const string DefaultDeliverers = 'hr.default-deliverers';

    public const string ArchiveUsers = 'hr.archive-users';

    public const string PositionManagers = 'hr.position-managers';

    /** @var array<string, list<string>> */
    private const array Parameters = [
        self::DefaultDeliverers => ['city'],
        self::ArchiveUsers => ['folder'],
        self::PositionManagers => ['position'],
    ];

    public function handles(string $scope): bool
    {
        return array_key_exists($scope, self::Parameters);
    }

    public function parameters(string $scope): array
    {
        return self::Parameters[$scope] ?? [];
    }

    public function options(string $scope, User $user, array $parameters): ?array
    {
        $id = fn (string $key): ?int => ctype_digit($parameters[$key] ?? '') ? (int) $parameters[$key] : null;

        return match ($scope) {
            self::DefaultDeliverers => $this->deliverers($user, $id('city')),
            self::ArchiveUsers => $this->archiveUsers($user, $id('folder')),
            self::PositionManagers => $this->managers($user, $id('position')),
            default => null,
        };
    }

    public function labels(string $scope, array $values, array $parameters): array
    {
        $ids = array_values(array_filter($values, fn (string $value): bool => ctype_digit($value)));

        if ($ids === []) {
            return [];
        }

        $rows = match ($scope) {
            self::DefaultDeliverers => $this->employeeRows(Employee::query()->whereKey($ids)->get(ChecklistPeople::NAME_COLUMNS)->all()),
            self::ArchiveUsers => $this->userRows($ids),
            self::PositionManagers => Position::pickerOptions(),
            default => [],
        };

        return collect($rows)->whereIn('value', $ids)->pluck('label', 'value')->all();
    }

    /**
     * @return list<array{value: string, label: string}>|null
     */
    private function deliverers(User $user, ?int $cityId): ?array
    {
        if ($cityId === null || Gate::forUser($user)->denies('create', [DefaultDeliverable::class, $cityId])) {
            return null;
        }

        if (! City::query()->whereKey($cityId)->whereHas('centers')->exists()) {
            return [];
        }

        return $this->employeeRows(ChecklistPeople::defaultDeliverersIn($cityId)
            ->orderBy(Employee::translatedColumn('first_name'))
            ->get(ChecklistPeople::NAME_COLUMNS)
            ->all());
    }

    /**
     * @return list<array{value: string, label: string}>|null
     */
    private function archiveUsers(User $user, ?int $folderId): ?array
    {
        $folder = $folderId === null ? null : Folder::query()->find($folderId);

        if ($folder === null || Gate::forUser($user)->denies('share', $folder)) {
            return null;
        }

        return $this->userRows();
    }

    /**
     * @return list<array{value: string, label: string}>|null
     */
    private function managers(User $user, ?int $positionId): ?array
    {
        $position = $positionId === null ? null : Position::query()->find($positionId);

        $allowed = $position === null
            ? $positionId === null && Gate::forUser($user)->allows('create', Position::class)
            : Gate::forUser($user)->allows('update', $position);

        if (! $allowed) {
            return null;
        }

        return array_values(array_filter(Position::pickerOptions(), fn (array $option): bool => (int) $option['value'] !== $positionId));
    }

    /**
     * @param  list<Employee>  $employees
     * @return list<array{value: string, label: string}>
     */
    private function employeeRows(array $employees): array
    {
        return array_map(fn (Employee $employee): array => ['value' => (string) $employee->id, 'label' => (string) $employee->name], $employees);
    }

    /**
     * Every login as «name · email», the archive's share list.
     *
     * @param  list<string>|null  $ids
     * @return list<array{value: string, label: string}>
     */
    private function userRows(?array $ids = null): array
    {
        return array_values(User::query()
            ->select(['id', 'email', 'employee_id'])
            ->when($ids !== null, fn ($query) => $query->whereKey($ids))
            ->orderBy('email')
            ->get()
            ->map(fn (User $user): array => ['value' => (string) $user->id, 'label' => trim($user->name.' · '.$user->email, ' ·')])
            ->all());
    }
}
