<?php

namespace App\Livewire\Recruitment\Approvals;

use App\Livewire\Concerns\AuthorizesActivePermissions;
use App\Services\RecruitmentApproverEligibility;
use App\Services\RecruitmentPendingApprovalQuery;
use Livewire\Component;
use Livewire\WithPagination;

class Pending extends Component
{
    use AuthorizesActivePermissions;
    use WithPagination;

    public function mount(): void
    {
        $this->authorizeActivePermission('recruitment-requests.approve');

        // Hidden from the menu for anyone no approval can reach; the address
        // is closed to them too, so hiding the link is not the only guard.
        abort_unless(app(RecruitmentApproverEligibility::class)->mayReceiveApprovals(auth()->user()), 403);
    }

    public function render()
    {
        $items = app(RecruitmentPendingApprovalQuery::class)
            ->forUser(auth()->user())
            ->paginate(15);

        return view('livewire.recruitment.approvals.pending', compact('items'));
    }
}
