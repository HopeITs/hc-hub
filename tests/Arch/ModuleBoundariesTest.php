<?php

use Pest\Arch\Repositories\ObjectsRepository;

/**
 * Module boundaries in "warning" mode (roadmap item 1.1).
 *
 * The classes that broke a rule when these tests were added are listed in
 * module-boundaries-allowlist.php. Every other class must follow the rule, so
 * a new forbidden import fails the run. The list may only shrink: a fixed class
 * must leave the list, and the list may never grow past its pinned `max`.
 */

/** @var array<string, array{source: string, forbidden: string, max: int, allowed: list<string>}> $rules */
$rules = require __DIR__.'/module-boundaries-allowlist.php';

/**
 * The PSR-4 class namespaces directly under a module or `App`.
 *
 * Lower-case folders (resources, database, routes) hold Blade views,
 * migrations and route files, which have no named classes for the arch plugin
 * to check. Leaving them out keeps the parsed source small enough to run.
 *
 * @return list<string>
 */
function moduleCodeNamespaces(string $namespace): array
{
    $roots = ['App' => 'app', 'Modules' => 'modules'];
    [$rootNamespace, $rest] = array_pad(explode('\\', $namespace, 2), 2, '');
    $directory = dirname(__DIR__, 2).'/'.$roots[$rootNamespace].($rest === '' ? '' : '/'.str_replace('\\', '/', $rest));
    $namespaces = [];

    foreach (new DirectoryIterator($directory) as $entry) {
        $name = $entry->getFilename();

        if ($entry->isDot() || ! ctype_upper($name[0])) {
            continue;
        }

        if ($entry->isDir()) {
            $namespaces[] = $namespace.'\\'.$name;
        } elseif (str_ends_with($name, '.php') && ! str_ends_with($name, '.blade.php')) {
            $namespaces[] = $namespace.'\\'.substr($name, 0, -4);
        }
    }

    sort($namespaces);

    return $namespaces;
}

/**
 * The classes under $source that use anything under $forbidden, read from the
 * same parsed source the arch expectations use. Unlike `ignoring()`, which
 * matches by name prefix, this compares exact class names.
 *
 * @return list<string>
 */
function classesBreakingModuleBoundary(string $source, string $forbidden): array
{
    $repository = ObjectsRepository::getInstance();
    $breaking = [];

    foreach (moduleCodeNamespaces($source) as $namespace) {
        foreach ($repository->allByNamespace($namespace, false) as $object) {
            foreach ($object->uses as $use) {
                if (str_starts_with($use, $forbidden.'\\')) {
                    $breaking[] = $object->name;

                    break;
                }
            }
        }
    }

    $breaking = array_values(array_unique($breaking));
    sort($breaking);

    return $breaking;
}

/**
 * Drops the parsed source the arch plugin caches for the whole run, so only
 * one rule's namespaces are held in memory at a time.
 */
function forgetParsedModuleSource(): void
{
    (fn () => $this->cachedObjectsPerPrefix = [])->call(ObjectsRepository::getInstance());
}

foreach ($rules as $rule => ['source' => $source, 'forbidden' => $forbidden, 'max' => $max, 'allowed' => $allowed]) {
    test("{$rule}: the allow-list matches the classes that break it, and only shrinks", function () use ($source, $forbidden, $max, $allowed): void {
        forgetParsedModuleSource();

        $breaking = classesBreakingModuleBoundary($source, $forbidden);

        expect(array_values(array_diff($breaking, $allowed)))
            ->toBe([], 'These classes newly break the rule. Fix the import; do not allow-list it.')
            ->and(array_values(array_diff($allowed, $breaking)))
            ->toBe([], 'These allow-listed classes no longer break the rule. Delete them from the allow-list and lower its max.')
            ->and($allowed)->toBe(array_values(array_unique($allowed)))
            ->and(count($allowed))->toBeLessThanOrEqual($max);
    });

    /*
     * One expectation per source namespace: with an array of targets,
     * `not->toUse()` passes as soon as one target is clean.
     */
    test("{$rule}, except the allow-listed classes", function () use ($source, $forbidden, $allowed): void {
        foreach (moduleCodeNamespaces($source) as $namespace) {
            expect($namespace)
                ->not->toUse(moduleCodeNamespaces($forbidden))
                ->ignoring($allowed);
        }
    })->group('arch');
}
