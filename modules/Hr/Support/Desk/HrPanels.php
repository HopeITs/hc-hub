<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Desk;

use Closure;
use Illuminate\Support\Facades\Route;
use Modules\Core\Models\User;
use Modules\Hr\Support\Desk\Cards\BadgesCard;
use Modules\Hr\Support\Desk\Cards\BirthdaysCard;
use Modules\Hr\Support\Desk\Cards\CalendarCard;
use Modules\Hr\Support\Desk\Cards\ChecklistCard;
use Modules\Hr\Support\Desk\Cards\ClockCard;
use Modules\Hr\Support\Desk\Cards\ContractTypesCard;
use Modules\Hr\Support\Desk\Cards\DecisionsCard;
use Modules\Hr\Support\Desk\Cards\DepartmentsCard;
use Modules\Hr\Support\Desk\Cards\EndingContractsCard;
use Modules\Hr\Support\Desk\Cards\EventsCard;
use Modules\Hr\Support\Desk\Cards\HrCard;
use Modules\Hr\Support\Desk\Cards\JobLevelsCard;
use Modules\Hr\Support\Desk\Cards\LateCard;
use Modules\Hr\Support\Desk\Cards\LeaveBalanceCard;
use Modules\Hr\Support\Desk\Cards\MyLeavesCard;
use Modules\Hr\Support\Desk\Cards\NewsCard;
use Modules\Hr\Support\Desk\Cards\NextHolidayCard;
use Modules\Hr\Support\Desk\Cards\NoLeavesCard;
use Modules\Hr\Support\Desk\Cards\PendingRequestsCard;
use Modules\Hr\Support\Desk\Cards\TeamCard;
use Modules\Hr\Support\Desk\Cards\UpcomingLeavesCard;
use Modules\Hr\Support\Desk\Cards\WhoIsOutCard;

/**
 * The HR cards of the Desk: the old HR dashboard's tiles as Desk panels.
 *
 * App\Support\Desk\Panels reads definitions() into its own catalogue, so an
 * HR card is placed, sized, pinned, folded and removed like any other panel.
 * Ids are "hr-<key>"; every card but the notes is drawn by one component
 * (hr::desk.card) from its HrCard class, the notes by hr::desk.notes.
 * Texts are hr_desk.cards.<key>.{title,description,tagline,empty,locked}.
 */
final class HrPanels
{
    public const string Prefix = 'hr-';

    /**
     * The personal notes card: a component of its own (it writes).
     */
    public const string Notes = 'hr-notes';

    /**
     * @var list<class-string<HrCard>>
     */
    private const array Cards = [
        DecisionsCard::class,
        LateCard::class,
        NewsCard::class,
        EventsCard::class,
        CalendarCard::class,
        ClockCard::class,
        LeaveBalanceCard::class,
        MyLeavesCard::class,
        NextHolidayCard::class,
        WhoIsOutCard::class,
        UpcomingLeavesCard::class,
        BirthdaysCard::class,
        TeamCard::class,
        PendingRequestsCard::class,
        ChecklistCard::class,
        BadgesCard::class,
        EndingContractsCard::class,
        DepartmentsCard::class,
        JobLevelsCard::class,
        ContractTypesCard::class,
        NoLeavesCard::class,
    ];

    /**
     * The starting Desk of the HR app's roles, in the order the cards appear
     * (role => [panel, size]). Read by Panels after its own role layouts, so
     * a Tracking role a person also holds still wins.
     *
     * @var array<string, list<array{0: string, 1: string}>>
     */
    public const array RoleLayouts = [
        'HR-SYR' => [['hr-decisions', 'L'], ['hr-late', 'L'], ['hr-ending-contracts', 'L'], ['hr-who-is-out', 'L'], ['hr-news', 'L'], ['hr-calendar', 'L'], ['hr-departments', 'M'], ['hr-job-levels', 'M'], ['hr-contract-types', 'M']],
        'HR-ALP' => [['hr-decisions', 'L'], ['hr-late', 'L'], ['hr-ending-contracts', 'L'], ['hr-who-is-out', 'L'], ['hr-news', 'L'], ['hr-calendar', 'L'], ['hr-departments', 'L'], ['hr-birthdays', 'L']],
        'HR-HMS' => [['hr-decisions', 'L'], ['hr-late', 'L'], ['hr-ending-contracts', 'L'], ['hr-who-is-out', 'L'], ['hr-news', 'L'], ['hr-calendar', 'L'], ['hr-departments', 'L'], ['hr-birthdays', 'L']],
        'HR-DMS' => [['hr-decisions', 'L'], ['hr-late', 'L'], ['hr-ending-contracts', 'L'], ['hr-who-is-out', 'L'], ['hr-news', 'L'], ['hr-calendar', 'L'], ['hr-departments', 'L'], ['hr-birthdays', 'L']],
        'HR-LAT' => [['hr-decisions', 'L'], ['hr-late', 'L'], ['hr-ending-contracts', 'L'], ['hr-who-is-out', 'L'], ['hr-news', 'L'], ['hr-calendar', 'L'], ['hr-departments', 'L'], ['hr-birthdays', 'L']],
        'HR-TAR' => [['hr-decisions', 'L'], ['hr-late', 'L'], ['hr-ending-contracts', 'L'], ['hr-who-is-out', 'L'], ['hr-news', 'L'], ['hr-calendar', 'L'], ['hr-departments', 'L'], ['hr-birthdays', 'L']],
        'general-manager' => [['needs', 'L'], ['hr-decisions', 'L'], ['hr-news', 'L'], ['hr-late', 'L'], ['hr-departments', 'M'], ['hr-job-levels', 'M'], ['hr-contract-types', 'M']],
        'manager' => [['hr-decisions', 'L'], ['hr-who-is-out', 'L'], ['hr-news', 'L'], ['hr-upcoming-leaves', 'L'], ['hr-team', 'L'], ['hr-leave-balance', 'L'], ['needs', 'L'], ['tasks', 'L']],
        'employee' => [['hr-news', 'L'], ['hr-leave-balance', 'L'], ['hr-clock', 'M'], ['hr-next-holiday', 'M'], ['hr-birthdays', 'M'], ['hr-my-leaves', 'L'], ['hr-who-is-out', 'L'], ['needs', 'L'], ['tasks', 'L']],
    ];

    /**
     * The HR card that stands for an HR page when it is dragged onto the Desk.
     *
     * @var array<string, string>
     */
    public const array RoutePanels = [
        'hr.inbox' => 'hr-decisions',
        'hr.news.index' => 'hr-news',
        'hr.contracts.index' => 'hr-ending-contracts',
        'hr.team.index' => 'hr-team',
        'hr.statistics.index' => 'hr-departments',
        'hr.leave.holidays.index' => 'hr-next-holiday',
    ];

    /**
     * @var array<string, HrCard>|null
     */
    private ?array $cards = null;

    /**
     * Every HR card by Desk id.
     *
     * @return array<string, HrCard>
     */
    public function cards(): array
    {
        if ($this->cards !== null) {
            return $this->cards;
        }

        $this->cards = [];

        foreach (self::Cards as $class) {
            $card = app($class);
            $this->cards[self::Prefix.$card->key()] = $card;
        }

        return $this->cards;
    }

    /**
     * The card behind a Desk id, if the user is offered it.
     */
    public function forUser(User $user, string $id): ?HrCard
    {
        $card = $this->cards()[$id] ?? null;

        return $card !== null && ($user->isAdministrator() || $card->allows($user)) ? $card : null;
    }

    public static function isHrPanel(string $id): bool
    {
        return str_starts_with($id, self::Prefix);
    }

    /**
     * The Livewire component that draws an HR panel.
     */
    public static function componentFor(string $id): string
    {
        return $id === self::Notes ? 'hr::desk.notes' : 'hr::desk.card';
    }

    /**
     * The HR cards as Panels definitions (App\Support\Desk\Panels::definitions).
     *
     * @return array<string, array{icon: string, size: string, rows: int, register: string|null, allows: Closure(User): bool, title: string, description: string, locked: string, kind: string}>
     */
    public function definitions(): array
    {
        $definitions = [];

        foreach ($this->cards() as $id => $card) {
            $definitions[$id] = $this->definition($card->key(), $card->icon(), $card->size(), $card->height(), $card->register(), $card->allows(...));
        }

        $definitions[self::Notes] = $this->definition('notes', 'check', 'M', 4, null, fn (User $user): bool => $user->employee_id !== null);

        return $definitions;
    }

    /**
     * @param  Closure(User): bool  $allows
     * @return array{icon: string, size: string, rows: int, register: string|null, allows: Closure(User): bool, title: string, description: string, locked: string, kind: string}
     */
    private function definition(string $key, string $icon, string $size, int $rows, ?string $register, Closure $allows): array
    {
        return [
            'icon' => $icon,
            'size' => $size,
            'rows' => $rows,
            'register' => $register !== null && Route::has($register) ? $register : null,
            'allows' => $allows,
            'title' => "hr_desk.cards.{$key}.title",
            'description' => "hr_desk.cards.{$key}.description",
            'locked' => 'hr_desk.locked',
            'kind' => 'people',
        ];
    }
}
