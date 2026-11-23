<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Search;

use App\Support\Like;
use App\Support\Modules\Contracts\SearchSource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Gate;
use Modules\Core\Models\User;
use Modules\Hr\Enums\ContractType as ContractTypeEnum;
use Modules\Hr\Models\Contract;

/**
 * Contracts in the Ask bar: found by the employee's name or by the contract's
 * own number, through Contract::visibleTo (a manager's direct reports, a city
 * HR's city, head office everything) and only for whoever may view contracts.
 * Pay is never selected: a row shows the person, the position and the end date.
 */
final class ContractSearchSource implements SearchSource
{
    public function key(): string
    {
        return 'contracts';
    }

    public function label(): string
    {
        return __('hr_search.contracts.label');
    }

    public function icon(): string
    {
        return 'file';
    }

    public function words(): array
    {
        return ['contract', 'contracts', 'عقد', 'عقود'];
    }

    public function allows(User $user): bool
    {
        return Gate::forUser($user)->allows('viewAny', Contract::class);
    }

    public function search(User $user, string $term, int $limit): array
    {
        $term = trim($term);

        if ($term === '' || $limit < 1) {
            return [];
        }

        $number = ctype_digit($term) ? (int) $term : null;

        return Contract::query()
            ->visibleTo($user)
            ->where('contracts.type', '!=', ContractTypeEnum::Draft->value)
            ->where(function (Builder $match) use ($term, $number): void {
                $match->whereHas('employee', fn (Builder $employee) => $employee->where(Like::everyWord($term, fn (Builder|QueryBuilder $fields, string $word) => $fields
                    ->where(Like::word('first_name_en', $word))
                    ->orWhere(Like::word('last_name_en', $word))
                    ->orWhere(Like::word('first_name_ar', $word))
                    ->orWhere(Like::word('last_name_ar', $word)), acrossLayouts: false)));

                if ($number !== null) {
                    $match->orWhere('contracts.id', $number);
                }
            })
            ->select(['contracts.id', 'contracts.employee_id', 'contracts.position_id', 'contracts.end_date', 'contracts.terminated_at'])
            ->with(['employee:id,first_name_en,first_name_ar,last_name_en,last_name_ar,username', 'position:id,title_en,title_ar'])
            ->orderByDesc('contracts.end_date')
            ->orderByDesc('contracts.id')
            ->limit($limit)
            ->get()
            ->map(function (Contract $contract): array {
                $ended = $contract->terminated_at !== null || $contract->end_date?->isPast() === true;

                return [
                    'title' => $contract->employee?->nameForLocale(app()->getLocale()) ?: '—',
                    'context' => implode(' · ', array_filter([
                        '#'.$contract->id,
                        $contract->position?->title,
                        $contract->end_date !== null
                            ? __($ended ? 'hr_search.contracts.ended_on' : 'hr_search.contracts.ends_on', ['date' => $contract->end_date->format('d/m/Y')])
                            : null,
                    ], fn (?string $part): bool => filled($part))),
                    'href' => route('hr.contracts.show', ['contract' => $contract->id]),
                ];
            })
            ->all();
    }
}
