<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Inbox;

use App\Support\Modules\Contracts\WorkItemSource;
use Illuminate\Support\Facades\Route;
use Modules\Core\Models\User;

/**
 * The HR work inbox as a source of the "Waiting on me" page (D7). Every row
 * and the count come from WorkInbox, the one reader behind `hr.inbox`, so
 * this source and that page always list the same items.
 */
final class InboxWorkItems implements WorkItemSource
{
    public function __construct(private readonly WorkInbox $inbox) {}

    public function key(): string
    {
        return 'hr-inbox';
    }

    public function label(): string
    {
        return (string) __('waiting.sources.hr-inbox');
    }

    public function allows(User $user): bool
    {
        return $this->inbox->hasSources($user);
    }

    public function href(User $user): ?string
    {
        return Route::has('hr.inbox') ? route('hr.inbox') : null;
    }

    public function count(User $user): int
    {
        return $this->inbox->count($user);
    }

    public function items(User $user): array
    {
        $items = [];

        foreach ($this->inbox->for($user)['rows'] as $row) {
            $action = (string) __('hr_communications.inbox.action.'.$row['action']);

            $items[] = [
                'ref' => (string) $row['key'],
                'title' => (string) $row['name'],
                'context' => $row['what'] === '' ? $action : $action.' · '.$row['what'],
                'href' => (string) $row['href'],
                'since' => $row['since'],
                'urgent' => $row['urgent'] || $row['started'],
            ];
        }

        return $items;
    }
}
