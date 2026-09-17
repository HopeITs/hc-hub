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
}
