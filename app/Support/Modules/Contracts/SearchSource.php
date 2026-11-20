<?php

declare(strict_types=1);

namespace App\Support\Modules\Contracts;

use Modules\Core\Models\User;

/**
 * One kind of record a module lets the Ask bar find by name (roadmap 4.11,
 * D10): employees today. Registered with ModuleRegistry::addSearch().
 *
 * The source owns its visibility: Ask never reads a source whose allows()
 * says no, and search() returns only rows this viewer may open, so a row
 * never links to a page that would answer 403 or 404. It returns at most
 * $limit rows and never loads the whole table.
 */
interface SearchSource
{
    /**
     * The source's name in the page's markup: lowercase letters and dashes.
     */
    public function key(): string;

    /**
     * The heading over the source's rows, in the reader's language.
     */
    public function label(): string;

    /**
     * A Desk icon name (App\Support\Desk\Icons), drawn in front of each row.
     */
    public function icon(): string;

    /**
     * Words that name this kind of record in English and Arabic ("employee",
     * "موظف"). Typed beside a name ("employee sami") they point at this
     * source and are not searched as part of the name.
     *
     * @return list<string>
     */
    public function words(): array;

    public function allows(User $user): bool;

    /**
     * The records whose name holds every word of the term, as typed (Ask
     * reads the other keyboard layout itself). The term is user text: bind
     * it, and match `%` and `_` literally (App\Support\Like).
     *
     * @return list<array{title: string, context: string, href: string}>
     */
    public function search(User $user, string $term, int $limit): array;
}
