<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Desk\Cards;

use App\Support\Desk\Cards\PageCard;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Route;
use Modules\Core\Models\User;
use Modules\Hr\Models\Employee;
use Modules\Hr\Support\Desk\HrViewer;

/**
 * One HR card of the Desk (the old HR dashboard's tiles, rebuilt as Desk
 * panels "hr-<key>"). It is a PageCard in everything it draws (figures and
 * shaped rows, one renderer: hr::desk.card), but it is offered by its own
 * rule (allows) instead of a page's, because most HR cards are about the
 * viewer or their team and have no register behind them.
 */
abstract class HrCard extends PageCard
{
    /**
     * Employee columns a row needs to print a name.
     *
     * @var list<string>
     */
    protected const array Person = Employee::NameColumns;

    /**
     * The card's key: its Desk id is "hr-<key>", its texts hr_desk.cards.<key>.*.
     */
    abstract public function key(): string;

    /**
     * Who is offered the card. An administrator is offered every card.
     */
    abstract public function allows(User $user): bool;

    /**
     * The page the card's "open in full" leads to, if it exists.
     */
    public function register(): ?string
    {
        return null;
    }

    public function route(): string
    {
        return (string) $this->register();
    }

    public function icon(): string
    {
        return 'users';
    }

    public function size(): string
    {
        return 'M';
    }

    /**
     * Row units the card is tall when first placed.
     */
    public function height(): int
    {
        return 4;
    }

    /**
     * What the card says when it has no rows.
     */
    public function emptyText(): string
    {
        return (string) __('hr_desk.cards.'.$this->key().'.empty');
    }

    /**
     * The card's state for its count colour: critical, warn, good, brand or null.
     */
    public function health(User $user): ?string
    {
        return null;
    }

    protected function viewer(User $user): HrViewer
    {
        return HrViewer::for($user);
    }

    /**
     * A link to a page that may not exist yet on this branch: empty until it does.
     *
     * @param  array<string, mixed>|mixed  $parameters
     */
    protected function link(string $route, mixed $parameters = []): string
    {
        return Route::has($route) ? route($route, $parameters) : '';
    }

    /**
     * A person's initials as a row's lead.
     *
     * @return array{kind: string, big: string, tone: string}
     */
    protected function face(int $id, string $name): array
    {
        return ['kind' => 'avatar', 'big' => $this->initials($name), 'tone' => 'tone-'.($id % 6)];
    }

    /**
     * A day as a row's lead: the day number over the month.
     *
     * @return array{kind: string, big: string, small: string, tone: string}
     */
    protected function day(CarbonInterface $date, string $tone = 'neutral'): array
    {
        return ['kind' => 'date', 'big' => $date->translatedFormat('j'), 'small' => $date->translatedFormat('M'), 'tone' => $tone];
    }

    /**
     * "12 Nov" or "12 – 14 Nov": a span of days said shortly.
     */
    protected function span(CarbonInterface $from, ?CarbonInterface $to): string
    {
        if ($to === null || $from->isSameDay($to)) {
            return $from->translatedFormat('j M');
        }

        return $from->isSameMonth($to)
            ? $from->translatedFormat('j').' – '.$to->translatedFormat('j M')
            : $from->translatedFormat('j M').' – '.$to->translatedFormat('j M');
    }

    /**
     * How far a day is from today, in words.
     */
    protected function away(CarbonInterface $date): string
    {
        $days = (int) today()->diffInDays($date->copy()->startOfDay(), false);

        return match (true) {
            $days === 0 => (string) __('hr_desk.today'),
            $days === 1 => (string) __('hr_desk.tomorrow'),
            $days > 1 => trans_choice('hr_desk.in_days', $days, ['count' => $days]),
            default => trans_choice('hr_desk.days_ago', -$days, ['count' => -$days]),
        };
    }
}
