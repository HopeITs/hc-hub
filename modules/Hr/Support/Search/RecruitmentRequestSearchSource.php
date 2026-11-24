<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Search;

use App\Support\Like;
use App\Support\Modules\Contracts\SearchSource;
use Illuminate\Support\Facades\Gate;
use Modules\Core\Models\User;
use Modules\Hr\Models\RecruitmentRequest;

/**
 * Hiring requests in the Ask bar: found by their number, through the scope
 * the requests list uses (RecruitmentRequest::visibleTo), for whoever may
 * view requests.
 */
final class RecruitmentRequestSearchSource implements SearchSource
{
    public function key(): string
    {
        return 'hiring-requests';
    }

    public function label(): string
    {
        return __('hr_search.requests.label');
    }

    public function icon(): string
    {
        return 'file';
    }

    public function words(): array
    {
        return ['request', 'requests', 'hiring', 'طلب', 'طلبات', 'توظيف'];
    }

    public function allows(User $user): bool
    {
        return Gate::forUser($user)->allows('viewAny', RecruitmentRequest::class);
    }

    public function search(User $user, string $term, int $limit): array
    {
        if (trim($term) === '' || $limit < 1) {
            return [];
        }

        return RecruitmentRequest::query()
            ->visibleTo($user)
            ->where('request_number', 'like', Like::contains($term))
            ->orderByDesc('id')
            ->limit($limit)
            ->get(['id', 'request_number', 'created_at'])
            ->map(fn (RecruitmentRequest $request): array => [
                'title' => (string) $request->request_number,
                'context' => (string) $request->created_at?->format('d/m/Y'),
                'href' => route('hr.recruitment.requests.show', ['requestId' => $request->id]),
            ])
            ->all();
    }
}
