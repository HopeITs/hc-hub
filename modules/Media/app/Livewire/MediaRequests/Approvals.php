<?php

namespace Modules\Media\Livewire\MediaRequests;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use Modules\Media\Enums\MediaRequestStatus as S;
use Modules\Media\Models\MediaRequest;
use Modules\Media\Support\MediaTeam;

/** Requests waiting for the program manager. The decision itself is taken on the request page. */
class Approvals extends Component
{
    public function render(): View
    {
        $team = app(MediaTeam::class);
        $user = auth()->user();
        $programIds = $team->managedProgramIds(MediaTeam::employeeOf($user));
        $everything = $user->myRole() === 'super-admin';

        $requests = MediaRequest::query()
            ->with(['requester', 'program', 'city'])
            ->where('status', S::ProgramApproval)
            ->when(! $everything, fn ($q) => $q->whereIn('program_id', $programIds ?: [0]))
            ->orderBy('needed_on')
            ->get();

        return view('livewire.media-requests.approvals', ['requests' => $requests]);
    }
}
