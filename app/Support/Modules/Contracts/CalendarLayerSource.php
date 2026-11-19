<?php

declare(strict_types=1);

namespace App\Support\Modules\Contracts;

use Carbon\CarbonImmutable;
use Modules\Core\Models\User;

/**
 * One layer a module adds to the calendar (calendar/index): dated items the
 * calendar draws beside its own, with a switch of their own in the type
 * filter. Registered with ModuleRegistry::addCalendarLayer().
 *
 * The layer decides who may see it; the calendar never offers, reads or
 * draws a layer whose allows() says no.
 */
interface CalendarLayerSource
{
    /**
     * The layer's name in the URL and in the page's markup: lowercase
     * letters and dashes.
     */
    public function key(): string;

    /**
     * The layer's name in the type filter, translated.
     */
    public function label(): string;

    /**
     * A Heroicon name, drawn in front of each of the layer's items.
     */
    public function icon(): string;

    /**
     * Whether the layer's items come before the day's own work (a holiday
     * says what kind of day it is) or after it.
     */
    public function leads(): bool;

    public function allows(User $user): bool;

    /**
     * The layer's items on each day between two days, an item that spans
     * several days repeated on each of them.
     *
     * @return array<string, list<array{id: string, title: string, context: string|null, href: string|null}>> keyed by Y-m-d
     */
    public function between(User $user, CarbonImmutable $from, CarbonImmutable $to): array;
}
