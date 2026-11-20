<?php

declare(strict_types=1);

namespace App\Support\Modules\Contracts;

use Closure;

/**
 * The organisation's numbers one module defines (roadmap 4.12, D10): each
 * number once, in the module that owns the tables it counts. Registered with
 * ModuleRegistry::addMetrics() and read only through
 * App\Support\Metrics\OrgMetrics, so the Desk, the statistics pages and the
 * reports can never disagree about a number.
 *
 * A definition counts; it does not decide who may see the number. The place
 * that shows it keeps its own permission and passes the governorates the
 * viewer's figures cover.
 */
interface MetricSource
{
    /**
     * The module's numbers by key (lowercase letters and underscores, unique
     * across every module). `label` is the number's name in the reader's
     * language. `resolve` receives the governorates to count in (city ids;
     * null = the whole organisation, never an empty list) and returns the
     * number.
     *
     * @return array<string, array{label: string, resolve: Closure(list<int>|null): int}>
     */
    public function metrics(): array;
}
