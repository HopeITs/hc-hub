<?php

namespace Tests\Support;

/**
 * The hub's source folders: the platform's own (app/, resources/views, lang,
 * routes) followed by the same folder of every module in modules/<Name>/.
 *
 * Tests that scan source files ("every view…", "every model…") go through
 * these, so a module's files are checked exactly like the platform's.
 */
final class HubPaths
{
    /** @return list<string> app/ and every modules/<Name>/app */
    public static function app(): array
    {
        return [app_path(), ...self::modules('app')];
    }

    /** @return list<string> resources/views and every modules/<Name>/resources/views */
    public static function views(): array
    {
        return [resource_path('views'), ...self::modules('resources/views')];
    }

    /** @return list<string> lang/ and every modules/<Name>/lang */
    public static function lang(): array
    {
        return [lang_path(), ...self::modules('lang')];
    }

    /** @return list<string> routes/ and every modules/<Name>/routes */
    public static function routes(): array
    {
        return [base_path('routes'), ...self::modules('routes')];
    }

    /** @return list<string> app/Models and every modules/<Name>/app/Models */
    public static function models(): array
    {
        return [app_path('Models'), ...self::modules('app/Models')];
    }

    /**
     * Each Livewire class folder with the namespace it maps to.
     *
     * @return array<string, string> path => namespace prefix (with trailing \)
     */
    public static function livewire(): array
    {
        $paths = [app_path('Livewire') => 'App\\Livewire\\'];

        foreach (self::moduleNames() as $module) {
            $path = base_path("modules/{$module}/app/Livewire");

            if (is_dir($path)) {
                $paths[$path] = "Modules\\{$module}\\Livewire\\";
            }
        }

        return $paths;
    }

    /** @return list<string> e.g. ['Hr'] */
    public static function moduleNames(): array
    {
        return array_map('basename', glob(base_path('modules/*'), GLOB_ONLYDIR) ?: []);
    }

    /** @return list<string> the existing modules/<Name>/<sub> folders */
    public static function modules(string $sub): array
    {
        return array_values(array_filter(
            array_map(fn (string $module) => base_path("modules/{$module}/{$sub}"), self::moduleNames()),
            'is_dir',
        ));
    }
}
