<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Search;

use App\Support\Like;
use App\Support\Modules\Contracts\SearchSource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Gate;
use Modules\Core\Models\User;
use Modules\Hr\Models\LeaveRequest;

/**
 * Leave requests in the Ask bar: found by the employee's name, from the same
 * set the leave pages let the viewer manage (LeaveRequest::manageableBy: a
 * manager's reports, a regional HR's cities, national HR everyone).
 */
final class LeaveRequestSearchSource implements SearchSource
{
    public function key(): string
    {
        return 'leave-requests';
    }

    public function label(): string
    {
        return __('hr_search.leave.label');
    }

    public function icon(): string
    {
        return 'cal';
    }

    public function words(): array
    {
        return ['leave', 'leaves', 'vacation', 'إجازة', 'اجازة', 'إجازات', 'اجازات'];
    }

    public function allows(User $user): bool
    {
        return Gate::forUser($user)->allows('viewAny', LeaveRequest::class);
    }

    public function search(User $user, string $term, int $limit): array
    {
        if (trim($term) === '' || $limit < 1) {
            return [];
        }

        return LeaveRequest::query()
            ->manageableBy($user)
            ->exceptFormerStaffPending()
            ->whereHas('leaveBalance.contract.employee', fn (Builder $employee) => $employee->where(Like::everyWord($term, fn (Builder|QueryBuilder $fields, string $word) => $fields
                ->where(Like::word('first_name_en', $word))
                ->orWhere(Like::word('last_name_en', $word))
                ->orWhere(Like::word('first_name_ar', $word))
                ->orWhere(Like::word('last_name_ar', $word)), acrossLayouts: false)))
            ->with(['leaveBalance.leaveType', 'leaveBalance.contract.employee'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(function (LeaveRequest $leave): array {
                $start = $leave->start_at?->format('d/m/Y');
                $end = $leave->end_at?->format('d/m/Y');

                return [
                    'title' => $leave->leaveBalance?->contract?->employee?->nameForLocale(app()->getLocale()) ?: '—',
                    'context' => implode(' · ', array_filter([
                        $leave->leaveBalance?->leaveType?->name,
                        $start === null ? null : ($start === $end || $end === null ? $start : $start.' → '.$end),
                    ], fn (?string $part): bool => filled($part))),
                    'href' => route('hr.leave.show', ['leaveRequest' => $leave->id]),
                ];
            })
            ->all();
    }
}
