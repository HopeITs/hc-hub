<?php

declare(strict_types=1);

namespace App\Support\WorkItems;

use App\Livewire\Concerns\Dashboard\ReadsDeskDecisions;
use App\Support\Modules\Contracts\WorkItemSource;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Modules\Core\Models\User;

/**
 * The Desk's Needs you as a source of the "Waiting on me" page (D7). It
 * hosts ReadsDeskDecisions, the trait the Needs you card and the Desk
 * greeting read, so the rules of what is a decision live in that one place.
 *
 * The trait reads the signed-in user, as a Desk panel does: asked about
 * anyone else, this source answers with nothing instead of someone else's
 * decisions.
 *
 * @property-read Collection<int, array{key: string, kind: string, title: string, context: string, who: string, since: CarbonImmutable|null, due: CarbonImmutable|null, late: bool, href: string, action: string, taskId: int|null}> $decisions
 */
final class NeedsWorkItems implements WorkItemSource
{
    use ReadsDeskDecisions;

    /**
     * The viewer's decisions, read once per request.
     *
     * @var array<int, Collection<int, array{key: string, kind: string, title: string, context: string, who: string, since: CarbonImmutable|null, due: CarbonImmutable|null, late: bool, href: string, action: string, taskId: int|null}>>
     */
    private array $read = [];

    public function key(): string
    {
        return 'needs';
    }

    public function label(): string
    {
        return (string) __('desk.panel.needs.title');
    }

    public function allows(User $user): bool
    {
        return true;
    }

    public function href(User $user): ?string
    {
        return null;
    }

    public function count(User $user): int
    {
        return $this->decisionsOf($user)->count();
    }

    public function items(User $user): array
    {
        $items = [];

        foreach ($this->decisionsOf($user) as $decision) {
            $items[] = [
                'ref' => $this->refOf($decision['key'], $decision['href']),
                'title' => $decision['title'],
                'context' => $decision['who'] === '' ? $decision['context'] : $decision['context'].' · '.$decision['who'],
                'href' => $decision['href'],
                'since' => $decision['since'],
                'urgent' => $decision['late'],
            ];
        }

        return $items;
    }

    /**
     * The trait reads its own rows as `$this->decisions`, the computed
     * property of a Livewire component; here that is the per-request read.
     */
    public function __get(string $name): mixed
    {
        return $name === 'decisions' ? $this->decisions() : null;
    }

    /**
     * The name other sources give the same item: an HR decision carries the
     * HR inbox's own key behind `hr-`, and a review is known by the page it
     * opens (My reviews lists one row per proposal, as Needs you does).
     */
    private function refOf(string $key, string $href): string
    {
        if (str_starts_with($key, 'hr-')) {
            return substr($key, 3);
        }

        return str_starts_with($key, 'review-') ? 'review:'.$href : 'needs:'.$key;
    }

    /**
     * @return Collection<int, array{key: string, kind: string, title: string, context: string, who: string, since: CarbonImmutable|null, due: CarbonImmutable|null, late: bool, href: string, action: string, taskId: int|null}>
     */
    private function decisionsOf(User $user): Collection
    {
        if (auth()->id() !== $user->id) {
            return new Collection;
        }

        return $this->read[$user->id] ??= $this->decisions();
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    protected function visible(Builder $query, string $module): Builder
    {
        return $this->canView($module) ? $query : $query->whereRaw('1 = 0');
    }

    protected function canView(string $module): bool
    {
        return auth()->user()?->can("{$module}.view") === true;
    }
}
