<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Search;

use App\Support\Like;
use App\Support\Modules\Contracts\SearchSource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Modules\Core\Models\User;
use Modules\Hr\Models\MailList;

/**
 * Mail lists in the Ask bar: found by name or address. The page that lists
 * them is open to everyone signed in, so is the search; a row opens the page.
 */
final class MailListSearchSource implements SearchSource
{
    public function key(): string
    {
        return 'mail-lists';
    }

    public function label(): string
    {
        return __('hr_search.lists.label');
    }

    public function icon(): string
    {
        return 'users';
    }

    public function words(): array
    {
        return ['list', 'lists', 'mail', 'email', 'group', 'قائمة', 'قوائم', 'بريد', 'مجموعة'];
    }

    public function allows(User $user): bool
    {
        return true;
    }

    public function search(User $user, string $term, int $limit): array
    {
        if (trim($term) === '' || $limit < 1) {
            return [];
        }

        return MailList::query()
            ->where(Like::everyWord($term, fn (Builder|QueryBuilder $fields, string $word) => $fields
                ->where(Like::word('name', $word))
                ->orWhere(Like::word('address', $word)), acrossLayouts: false))
            ->orderBy('position')
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->map(fn (MailList $list): array => [
                'title' => (string) $list->name,
                'context' => $list->address,
                'href' => route('hr.mail-lists.index'),
            ])
            ->all();
    }
}
