<?php

declare(strict_types=1);

namespace App\Support\Metrics;

use App\Support\Modules\Contracts\MetricSource;
use App\Support\Modules\ModuleRegistry;
use Closure;
use InvalidArgumentException;
use LogicException;

/**
 * The one place an organisational number is read from (roadmap 4.12): "how
 * many employees are on projects", "how many projects are running". Each
 * number has one definition, registered by the module that owns its tables
 * (MetricSource, ModuleRegistry::addMetrics()); every Desk card, statistics
 * page and report asks here by key, so two places can never show two
 * answers to one question.
 *
 * Nothing is remembered between reads: a number is one grouped query, and a
 * cache in one place and not the other is exactly how two screens drift
 * apart. A definition that needs a cache keeps it inside itself, where every
 * reader shares it.
 *
 * Permissions stay with the place that shows the number; it passes the
 * governorates the viewer's figures cover.
 */
final class OrgMetrics
{
    /**
     * @var array<string, array{label: string, resolve: Closure(list<int>|null): int}>|null
     */
    private ?array $definitions = null;

    public function __construct(private readonly ModuleRegistry $modules) {}

    /**
     * The number, counted in the given governorates.
     *
     * @param  list<int>|null  $cities  city ids; null = the whole organisation, [] = none (always 0)
     */
    public function value(string $key, ?array $cities = null): int
    {
        $definition = $this->definition($key);

        if ($cities === []) {
            return 0;
        }

        return $definition['resolve']($cities === null ? null : array_values(array_unique(array_map(intval(...), $cities))));
    }

    /**
     * The number's name in the reader's language.
     */
    public function label(string $key): string
    {
        return $this->definition($key)['label'];
    }

    public function has(string $key): bool
    {
        return isset($this->definitions()[$key]);
    }

    /**
     * @return list<string>
     */
    public function keys(): array
    {
        return array_keys($this->definitions());
    }

    /**
     * @return array{label: string, resolve: Closure(list<int>|null): int}
     */
    private function definition(string $key): array
    {
        return $this->definitions()[$key] ?? throw new InvalidArgumentException("No module defines the organisational number [{$key}].");
    }

    /**
     * Every registered definition. Two modules defining one key is a
     * programming error: a number has one owner.
     *
     * @return array<string, array{label: string, resolve: Closure(list<int>|null): int}>
     */
    private function definitions(): array
    {
        if ($this->definitions !== null) {
            return $this->definitions;
        }

        $definitions = [];

        foreach ($this->modules->metricSources() as $source) {
            foreach ($source->metrics() as $key => $definition) {
                if (isset($definitions[$key])) {
                    throw new LogicException("The organisational number [{$key}] is defined twice; the second definition is in ".$source::class.'.');
                }

                $definitions[$key] = $definition;
            }
        }

        return $this->definitions = $definitions;
    }
}
