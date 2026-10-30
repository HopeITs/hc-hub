<?php

namespace App\Livewire\Notifications;

use App\Enums\NotificationKind;
use App\Enums\NotificationPriority;
use App\Livewire\Concerns\HandlesLoadFailure;
use App\Livewire\Concerns\HandlesNotificationActions;
use App\Models\UserNotification;
use App\Notifications\Inbox;
use App\Notifications\UnreadCounts;
use App\Services\NotificationRequestState;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Throwable;

/**
 * The hub's one notification bell (tracking's bell, plan decision 14): every
 * module's rows in one panel, with tabs (all / unread / alerts), module chips,
 * a pinned section for unread critical rows, and per-row read, unread and
 * snooze. HR's rules stay: the number leaves out requests somebody already
 * handled (G-C-12), a row says whether its request still waits, and opening
 * one marks it read before going to a checked internal link.
 *
 * The bell renders on every page, so until it is opened only the unread
 * counts are read; it polls those and rings when the number grows.
 */
class Bell extends Component
{
    use HandlesLoadFailure;
    use HandlesNotificationActions;

    /** Which slice the panel shows: all, unread or alerts. */
    public string $tab = 'all';

    /** The module chip the panel is narrowed to, '' for every module. */
    public string $module = '';

    /** Whether the panel has been opened on this page; rows load only then. */
    public bool $opened = false;

    /** The number last shown, so a poll can tell that something arrived. */
    public int $count = 0;

    public function mount(): void
    {
        $this->count = $this->counts()['total'];
    }

    public function loadPanel(): void
    {
        $this->opened = true;
    }

    public function showTab(string $tab): void
    {
        $this->tab = in_array($tab, ['all', 'unread', 'alerts'], true) ? $tab : 'all';
    }

    public function showModule(string $module): void
    {
        $this->module = $this->module === $module ? '' : $module;
    }

    /** The poll: a larger number than last time rings the bell. */
    public function refreshCounts(): void
    {
        $total = $this->counts()['total'];

        if ($total > $this->count) {
            $this->dispatch('notif-arrived');
        }

        $this->count = $total;
    }

    #[On('notification-read')]
    #[On('notification-created')]
    #[On('center-created')]
    #[On('center-closed')]
    public function refreshList(): void
    {
        $this->count = $this->counts()['total'];
    }

    public function markUnread(string $id): void
    {
        if (app(Inbox::class)->markUnread(auth()->user(), $id) > 0) {
            $this->dispatch('notification-read');
        }
    }

    public function snooze(string $id, string $preset): void
    {
        if (! in_array($preset, Inbox::SNOOZE_PRESETS, true)) {
            return;
        }

        app(Inbox::class)->snooze(auth()->user(), $id, $preset);
        $this->dispatch('notification-read');
    }

    public function render(): View
    {
        $counts = $this->counts();

        // Reads only; retryLoad() never writes, so a retry cannot mark anything read.
        [$sections, $states] = $this->opened
            ? $this->guardedLoad(fn (): array => $this->rows(), fallback: [collect(), []])
            : [collect(), []];

        return view('livewire.notifications.bell', [
            'counts' => $counts,
            'sections' => $sections,
            'states' => $states,
        ]);
    }

    /**
     * The latest 30 visible rows of the current slice, bucketed: pinned
     * (unread critical), today, yesterday, earlier; and their request states.
     *
     * @return array{0: Collection<array-key, DatabaseNotificationCollection<int, UserNotification>>, 1: array<string, array{state: string, status: ?string, next_step: ?string}>}
     */
    private function rows(): array
    {
        $order = ['pinned', 'today', 'yesterday', 'earlier'];

        $rows = UserNotification::query()
            ->forUser(auth()->user())
            ->visible()
            ->when($this->tab === 'unread', fn ($query) => $query->whereNull('read_at'))
            ->when($this->tab === 'alerts', fn ($query) => $query->whereIn('kind', [NotificationKind::Alert->value, NotificationKind::Digest->value]))
            ->when($this->module === 'hub', fn ($query) => $query->where(fn ($query) => $query->where('module', 'hub')->orWhereNull('module')))
            ->when($this->module !== '' && $this->module !== 'hub', fn ($query) => $query->where('module', $this->module))
            ->orderByRaw('case when read_at is null and priority = ? then 0 else 1 end', [NotificationPriority::Critical->value])
            ->latest()
            ->limit(30)
            ->get();

        $sections = $rows
            ->groupBy(fn (UserNotification $notification): string => match (true) {
                $notification->read_at === null && $notification->priority === NotificationPriority::Critical => 'pinned',
                (bool) $notification->created_at?->isToday() => 'today',
                (bool) $notification->created_at?->isYesterday() => 'yesterday',
                default => 'earlier',
            })
            ->sortBy(fn (Collection $rows, string $section): int => (int) array_search($section, $order, true));

        return [$sections, $this->requestStates($rows)];
    }

    /**
     * Unread counts per module and critical, with the total HR's way: less the
     * unread requests somebody already handled (G-C-12). A failed count shows
     * what is on screen; the next poll retries.
     *
     * @return array{total: int, critical: int, modules: array<string, int>}
     */
    private function counts(): array
    {
        try {
            $user = auth()->user();
            $counts = app(UnreadCounts::class)->for($user);
            $counts['total'] = app(NotificationRequestState::class)->unreadCount($user, $counts['total']);

            return $counts;
        } catch (Throwable $e) {
            report($e);

            return ['total' => $this->count, 'critical' => 0, 'modules' => []];
        }
    }
}
