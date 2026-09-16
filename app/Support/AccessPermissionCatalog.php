<?php

namespace App\Support;

use Illuminate\Support\Collection;

final class AccessPermissionCatalog
{
    /**
     * @return array<string, array<int, string>|array{prefix: string, actions: array<int, string>}>
     */
    public static function groups(): array
    {
        return config('access-control.groups', []);
    }

    /**
     * @return array<int, string>
     */
    public static function names(): array
    {
        return collect(self::groups())
            ->flatMap(function (array $definition, string $group): array {
                [$prefix, $actions] = self::normalize($group, $definition);

                return array_map(
                    fn (string $action): string => "{$prefix}.{$action}",
                    $actions,
                );
            })
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, array{name: string, label: string, permissions: Collection}>
     */
    public static function forDisplay(Collection $permissions): Collection
    {
        return collect(self::groups())->map(function (array $definition, string $group) use ($permissions): array {
            [$prefix, $actions] = self::normalize($group, $definition);

            return [
                'name' => $group,
                'label' => __("access.groups.{$group}"),
                'permissions' => collect($actions)->map(function (string $action) use ($prefix, $permissions): array {
                    $name = "{$prefix}.{$action}";

                    return [
                        'id' => (int) $permissions->firstWhere('name', $name)?->id,
                        'name' => $name,
                        'label' => __("access.actions.{$action}"),
                    ];
                })->filter(fn (array $permission): bool => $permission['id'] > 0),
            ];
        })->values();
    }

    /**
     * Existing groups use their display key as the permission prefix. Newer
     * modules may provide an explicit dotted prefix while keeping a flat,
     * translation-friendly display key.
     *
     * @param  array<int, string>|array{prefix: string, actions: array<int, string>}  $definition
     * @return array{0: string, 1: array<int, string>}
     */
    private static function normalize(string $group, array $definition): array
    {
        if (isset($definition['prefix'], $definition['actions'])) {
            return [$definition['prefix'], $definition['actions']];
        }

        return [$group, $definition];
    }
}
