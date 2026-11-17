<?php

declare(strict_types=1);

namespace App\Support\Modules\Contracts;

/**
 * Where a module's records lead from a notification or activity row, for
 * App\Notifications\SubjectLinks. Registered with
 * ModuleRegistry::addSubjectLinks().
 */
interface SubjectLinkSource
{
    /**
     * Whether a morph type is one of this module's records.
     */
    public function handles(string $type): bool;

    /**
     * The page for one of this module's records, or null when it no longer
     * exists. Only called for a type handles() accepts.
     */
    public function routeFor(string $type, int $id): ?string;
}
