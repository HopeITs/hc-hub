<?php

declare(strict_types=1);

namespace App\Support\Modules\Contracts;

use Carbon\CarbonInterface;
use Modules\Core\Models\User;

/**
 * One place where work waits on a person (roadmap 4.9, D7): the HR inbox,
 * the Desk's Needs you, My reviews. Registered with
 * ModuleRegistry::addWorkItems() and read by the "Waiting on me" page.
 *
 * A source never states its rules a second time: it reads the same
 * computation its own page or Desk card reads, so the page and the card can
 * never disagree on what waits or on how many.
 */
interface WorkItemSource
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
     * Whether anything from this source can ever wait on the user. The page
     * never reads a source whose allows() says no.
     */
    public function allows(User $user): bool;

    /**
     * The source's own page, when the user may open one.
     */
    public function href(User $user): ?string;

    /**
     * How many items wait on the user: the number the source's own page or
     * Desk card reports.
     */
    public function count(User $user): int;

    /**
     * What waits on the user, in the source's own order. `ref` names the
     * record and the step asked of the user, the same in every source that
     * lists it (a leave to decide is in the HR inbox and in Needs you), so
     * the page can show it once. `since` is when the wait began, `urgent`
     * the source's own word for late or pressing.
     *
     * @return list<array{ref: string, title: string, context: string, href: string, since: CarbonInterface|null, urgent: bool}>
     */
    public function items(User $user): array;
}
