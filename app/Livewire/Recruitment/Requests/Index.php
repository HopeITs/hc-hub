<?php

namespace App\Livewire\Recruitment\Requests;

use App\Enums\RecruitmentRequestStatus;
use App\Models\RecruitmentRequest;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

/**
 * The requests a person may see, with the statuses they care about on top.
 *
 * The tabs are a filter, not a different list: each one narrows the same
 * scoped query rather than reading from somewhere else, so a request cannot
 * appear under a tab it would not appear under in "all".
 */
class Index extends Component
{
    public string $statusFilter = 'all';

    public function setActiveTab(string $status): void
    {
        $this->statusFilter = $status;
    }

    public function render()
    {
        Gate::authorize('viewAny', RecruitmentRequest::class);

        return view('livewire.recruitment.requests.index', [
            'counts' => $this->counts(),
            'tabs' => $this->tabs(),
        ]);
    }

    /**
     * The statuses worth a tab of their own.
     *
     * Not all ten: a tab per status would be a row of mostly-empty buttons.
     * These are the ones somebody actually goes looking for — what is still
     * mine to finish, what is waiting on other people, and what is done.
     *
     * @return array<int, int>
     */
    private function tabs(): array
    {
        return [
            RecruitmentRequestStatus::Draft,
            RecruitmentRequestStatus::UnderReview,
            RecruitmentRequestStatus::ChangesRequested,
            RecruitmentRequestStatus::Approved,
            RecruitmentRequestStatus::Applied,
            RecruitmentRequestStatus::Rejected,
        ];
    }

    /**
     * How many requests sit behind each tab.
     *
     * Counted through the same visibility boundary as the table, so a number
     * never promises rows the viewer cannot open.
     *
     * @return array<string, int>
     */
    private function counts(): array
    {
        $visible = RecruitmentRequest::query()->visibleTo(auth()->user());

        $counts = ['all' => (clone $visible)->count()];

        foreach ($this->tabs() as $status) {
            $counts[(string) $status] = (clone $visible)->where('status', $status)->count();
        }

        return $counts;
    }
}
