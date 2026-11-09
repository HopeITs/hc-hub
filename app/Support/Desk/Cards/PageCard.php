<?php

declare(strict_types=1);

namespace App\Support\Desk\Cards;

use App\Enums\StatusTone;
use App\Models\User;
use App\Support\ArabicText;
use BackedEnum;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;

/**
 * A page in card form: what a register page is about, said in the space of a
 * Desk card. Every card has the same three parts, so it reads the same at any
 * size (components/desk/page-card): a few headline figures, an optional split
 * of the whole by state, and the page's records as rows that open in a Desk
 * tab. The card component decides how much of each fits the card it is in.
 *
 * A card is only offered to someone who may open its page (Destinations), and
 * reads its rows with the same rules as the page, so it never shows a row the
 * page would hide.
 */
abstract class PageCard
{
    /**
     * How many rows a card reads at most: a card is a glance, its page holds the rest.
     */
    public const int RowLimit = 60;

    /**
     * The register this card stands for (a Destinations route).
     */
    abstract public function route(): string;

    /**
     * Headline figures, the most telling first. The card shows as many as fit.
     *
     * @return list<array{label: string, value: string, tone: string}>
     */
    abstract public function figures(User $user): array;

    /**
     * The page's records, most worth a look first, at most RowLimit. $search
     * is what was typed in the card's search; a card may narrow in SQL, and
     * rows() narrows the rest by title and meta.
     *
     * @return Collection<int, array{key: string, title: string, meta: string, badge: array{label: string, tone: string}|null, href: string}>
     */
    abstract protected function records(User $user, string $search): Collection;

    /**
     * The whole split by state (each part's label, count and tone), drawn as
     * one bar; empty when the page has no states worth splitting by.
     *
     * @return list<array{label: string, count: int, tone: string}>
     */
    public function breakdown(User $user): array
    {
        return [];
    }

    /**
     * The rows the card lists: the page's records, narrowed by the search in
     * either language and on either keyboard layout.
     *
     * @return Collection<int, array{key: string, title: string, meta: string, badge: array{label: string, tone: string}|null, href: string}>
     */
    public function rows(User $user, string $search = ''): Collection
    {
        $search = trim($search);
        $rows = $this->records($user, $search)->take(self::RowLimit)->values();

        return $search === ''
            ? $rows
            : $rows->filter(fn (array $row): bool => ArabicText::containsTyped($row['title'].' '.$row['meta'], $search))->values();
    }

    /**
     * @return array{label: string, value: string, tone: string}
     */
    protected function figure(string $label, int|float|string $value, string $tone = 'neutral'): array
    {
        return ['label' => $label, 'value' => is_string($value) ? $value : $this->number($value), 'tone' => $tone];
    }

    /**
     * @param  array{label: string, tone: string}|null  $badge
     * @return array{key: string, title: string, meta: string, badge: array{label: string, tone: string}|null, href: string}
     */
    protected function row(string $key, string $title, string $meta, ?array $badge, string $href): array
    {
        return ['key' => $key, 'title' => $title, 'meta' => $meta, 'badge' => $badge, 'href' => $href];
    }

    /**
     * A status enum as a badge: its label and the shared tone (W-28).
     *
     * @return array{label: string, tone: string}
     */
    protected function badgeOf(BackedEnum $status): array
    {
        $tone = method_exists($status, 'tone') ? $status->tone() : StatusTone::Neutral;

        return [
            'label' => method_exists($status, 'label') ? (string) $status->label() : (string) $status->value,
            'tone' => $tone instanceof StatusTone ? $tone->value : 'neutral',
        ];
    }

    /**
     * @return array{label: string, tone: string}
     */
    protected function activeBadge(bool $active): array
    {
        return $active
            ? ['label' => (string) __('desk-workspace.card.active'), 'tone' => StatusTone::Good->value]
            : ['label' => (string) __('desk-workspace.card.inactive'), 'tone' => StatusTone::Neutral->value];
    }

    /**
     * A whole number in the reader's language; plain digits where PHP's intl
     * extension is missing, so a figure always shows.
     */
    protected function number(int|float $value): string
    {
        return extension_loaded('intl') ? (string) Number::format($value, maxPrecision: 0) : number_format($value);
    }

    /**
     * The breakdown with its empty parts left out.
     *
     * @param  list<array{label: string, count: int, tone: string}>  $parts
     * @return list<array{label: string, count: int, tone: string}>
     */
    protected function parts(array $parts): array
    {
        return array_values(array_filter($parts, fn (array $part): bool => $part['count'] > 0));
    }
}
