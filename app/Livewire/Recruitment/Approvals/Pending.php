<?php

namespace App\Livewire\Recruitment\Approvals;

use App\Livewire\Concerns\AuthorizesActivePermissions;
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
    }

    public function render()
    {
        $items = app(RecruitmentPendingApprovalQuery::class)
            ->forUser(auth()->user())
            ->paginate(15);

        return view('livewire.recruitment.approvals.pending', compact('items'));
    }
}
