<?php

namespace Modules\Media\Livewire\MediaRequests;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Modules\Media\Enums\MediaRequestStatus as S;
use Modules\Media\Models\MediaRequest;
use Modules\Media\Support\MediaTeam;

/**
 * My requests and my programs' requests. Whatever is waiting on the person
 * looking at the screen comes first, then by priority and needed-by date.
 */
class Index extends Component
{
    #[Url(as: 'tab')]
    public string $tab = 'all';

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'service')]
    public string $service = '';

    public function setActiveTab(string $tab): void
    {
        $this->tab = in_array($tab, ['all', 'action', 'open', 'done'], true) ? $tab : 'all';
    }

    #[On('media-request-saved')]
    public function refreshList(): void
    {
        // Re-render after the wizard saved a request.
    }

    /** People who only ask for work have the home screen only. */
    public function mount(): void
    {
        if (app(MediaTeam::class)->isRequesterOnly(auth()->user())) {
            $this->redirectRoute('media-requests.home');
        }
    }

    public function render(): View
    {
        $requests = MediaRequest::query()
            ->with(['requester', 'assignee', 'program', 'city'])
            ->relevantTo(auth()->user())
            ->when($this->service !== '', fn (Builder $q) => $q->where('service', $this->service))
            ->when(trim($this->search) !== '', function (Builder $q): void {
                $term = '%'.trim($this->search).'%';
                $q->where(fn (Builder $w) => $w->where('reference', 'like', $term)->orWhere('title', 'like', $term));
            })
            ->latest('id')
            ->limit(300)
            ->get();

        $mine = fn (MediaRequest $r): bool => Gate::allows('isOwner', $r);

        $counts = [
            'all' => $requests->count(),
            'action' => $requests->filter($mine)->count(),
            'open' => $requests->filter->isOpen()->count(),
            'done' => $requests->where('status', S::Delivered)->count(),
        ];

        $visible = match ($this->tab) {
            'action' => $requests->filter($mine),
            'open' => $requests->filter->isOpen(),
            'done' => $requests->where('status', S::Delivered),
            default => $requests,
        };

        return view('livewire.media-requests.index', [
            'requests' => $this->sorted($visible, $mine),
            'counts' => $counts,
            'mine' => $mine,
        ]);
    }

    /**
     * Needs you first, then open ones, then priority, then the nearest needed-by date.
     *
     * @param  Collection<int, MediaRequest>  $requests
     * @return Collection<int, MediaRequest>
     */
    private function sorted(Collection $requests, callable $mine): Collection
    {
        $weight = ['high' => 0, 'normal' => 1, 'low' => 2];
        $key = fn (MediaRequest $r): array => [
            (int) ! $mine($r),
            (int) ! $r->isOpen(),
            $weight[$r->priority] ?? 1.5,
            $r->needed_on->timestamp ?? PHP_INT_MAX,
        ];

        return $requests->sort(fn (MediaRequest $a, MediaRequest $b): int => $key($a) <=> $key($b))->values();
    }
}
