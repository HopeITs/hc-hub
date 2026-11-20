<?php

declare(strict_types=1);

namespace App\Support\Modules\Contracts;

use Carbon\CarbonImmutable;
use Modules\Core\Models\User;

/**
 * One kind of dated fact a module adds to an employee's timeline (roadmap
 * 4.7, D6): contracts, project memberships, equipment, journeys. Registered
 * with ModuleRegistry::addTimeline().
 *
 * A source reads the lasting tables that own the facts, never the activity
 * log: that log is pruned after some years (R8), and a person's history must
 * outlive it. The source decides who may see its entries; the timeline never
 * reads a source whose allows() says no.
 */
interface TimelineSource
{
    /**
     * The source's name in the page's markup and filter: lowercase letters
     * and dashes.
     */
    public function key(): string;

    /**
     * The source's name in the reader's language.
     */
    public function label(): string;

    /**
     * A Heroicon name, drawn in front of each of the source's entries.
     */
    public function icon(): string;

    public function allows(User $viewer, int $employeeId): bool;

    /**
     * What happened to one employee. A null date means the fact is real but
     * its day was never recorded (an inferred project membership): the
     * timeline lists it apart and invents no date.
     *
     * @return list<array{date: CarbonImmutable|null, title: string, detail: string|null, href: string|null}>
     */
    public function entries(User $viewer, int $employeeId): array;
}
