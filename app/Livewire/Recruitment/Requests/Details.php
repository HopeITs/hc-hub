<?php

namespace App\Livewire\Recruitment\Requests;

use App\Enums\RecruitmentRequestStatus;
use App\Models\Center;
use App\Models\ContractType;
use App\Models\Department;
use App\Models\JobLevel;
use App\Models\Position;
use App\Models\Program;
use App\Models\RecruitmentRequest;
use App\Services\RecruitmentRequestSubmissionService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Details extends Component
{
    #[Locked]
    public int $requestId;

    public ?int $versionId = null;

    public function mount(int $requestId): void
    {
        $this->requestId = $requestId;
    }

    /**
     * Submit (T3) or resubmit (T11).
     *
     * The service owns every rule; this only hands it the request and reports
     * what came back. Re-resolving through the visibility boundary first means
     * a stale page cannot act on a request the user has since lost.
     */
    public function submit(RecruitmentRequestSubmissionService $submissions): void
    {
        $request = $this->resolve();
        $before = (int) $request->status;

        $request = $submissions->submit(auth()->user(), $request);

        // A second click finds the work already done and says so plainly.
        session()->flash('recruitment_saved', __(
            $before === (int) $request->status ? 'recruitment.already_submitted' : 'recruitment.submitted',
        ));
    }

    /** Cancel while the request is still the requester's to withdraw. */
    public function cancelRequest(RecruitmentRequestSubmissionService $submissions): void
    {
        $submissions->cancel(auth()->user(), $this->resolve());

        session()->flash('recruitment_saved', __('recruitment.cancelled'));
    }

    public function render()
    {
        Gate::authorize('viewAny', RecruitmentRequest::class);
        // Resolve through the same visibility boundary on every Livewire update.
        $request = RecruitmentRequest::query()->visibleTo(auth()->user())
            ->with(['requester.profile', 'scopeCity', 'targetPosition',
                'versions' => fn ($query) => $query->orderByDesc('version_no'),
                'events' => fn ($query) => $query->with('actor.profile')->orderBy('created_at')->orderBy('id'),
                'comments' => fn ($query) => $query->with('user.profile')->orderBy('created_at')->orderBy('id'),
                'externalConfirmations.recordedBy.profile'])
            ->findOrFail($this->requestId);
        Gate::authorize('view', $request);

        $version = $this->versionId === null
            ? $request->versions->first()
            : $request->versions()->findOrFail($this->versionId);
        $version?->load('positionDetail');

        $draftContext = [];
        if ($request->status === RecruitmentRequestStatus::Draft) {
            foreach ([
                'direct_manager_id' => ['parent_position', Position::class, 'title'],
                'program_id' => ['program', Program::class, 'name'],
                'center_id' => ['center', Center::class, 'name'],
                'department_id' => ['department', Department::class, 'name'],
                'job_level_id' => ['job_level', JobLevel::class, 'name'],
                'contract_type_id' => ['contract_type', ContractType::class, 'name'],
            ] as $field => [$label, $model, $attribute]) {
                $id = $request->draft_payload_json[$field] ?? null;
                if (is_numeric($id)) {
                    $draftContext[$label] = $model::find((int) $id)?->{$attribute} ?? '—';
                }
            }
        }

        return view('livewire.recruitment.requests.details', compact('request', 'version', 'draftContext'));
    }

    private function resolve(): RecruitmentRequest
    {
        $request = RecruitmentRequest::query()->visibleTo(auth()->user())->findOrFail($this->requestId);
        Gate::authorize('view', $request);

        return $request;
    }
}
