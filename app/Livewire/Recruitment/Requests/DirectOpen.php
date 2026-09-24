<?php

namespace App\Livewire\Recruitment\Requests;

use App\Enums\ExternalConfirmationDecision;
use App\Enums\ExternalConfirmationMethod;
use App\Enums\RecruitmentRequestType;
use App\Enums\RecruitmentScopeType;
use App\Models\Center;
use App\Models\City;
use App\Models\ContractType;
use App\Models\Department;
use App\Models\JobLevel;
use App\Models\Position;
use App\Models\Program;
use App\Models\RecruitmentRequest;
use App\Models\Vacancy;
use App\Services\RecruitmentDirectOpenService;
use App\Services\RecruitmentScopeResolver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Component;

/**
 * Recording a hire that was settled before anybody opened a screen.
 *
 * The request half of this form is the ordinary one — a direct opening may
 * shorten the approvals, never the facts. What is added is the documentation
 * that makes it honest: who actually asked, why the workflow was bypassed, and
 * three lines saying how each approval was really given.
 *
 * The lists here are narrowed to the governorate this HR answers for, which is
 * a different boundary from the management tree a manager's own request is
 * scoped by — HR does not manage the posts they recruit for. The service
 * re-derives the same boundary and refuses anything outside it.
 */
class DirectOpen extends Component
{
    /** @var array<string, mixed> */
    public array $data = ['request_type' => RecruitmentRequestType::FillVacancy];

    /** @var array<string, mixed> */
    public array $documentation = [
        'actual_requester_name' => '',
        'actual_requester_position_id' => null,
        'direct_open_reason' => '',
        'confirmations' => [],
    ];

    public function boot(): void
    {
        $this->authorizeScreen();
    }

    public function updatedDataRequestType(): void
    {
        $this->data = ['request_type' => (int) $this->data['request_type']];
        $this->resetValidation();
    }

    public function updatedDataPositionId(): void
    {
        unset($this->data['vacancy_id']);
    }

    public function updatedDataCityId(): void
    {
        unset($this->data['center_id']);
    }

    public function save(RecruitmentDirectOpenService $directOpen)
    {
        $this->resetValidation();

        try {
            $request = $directOpen->open(auth()->user(), $this->data, $this->documentation);
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                $this->addError($field, $messages[0]);
            }

            return null;
        }

        $this->toast(__('recruitment.direct_open.opened'));

        return $this->redirectRoute('recruitment.requests.show', ['requestId' => $request->id]);
    }

    public function render()
    {
        $this->authorizeScreen();

        $user = auth()->user();
        $scopes = app(RecruitmentScopeResolver::class);
        $type = (int) ($this->data['request_type'] ?? 0);
        $newPosition = $type === RecruitmentRequestType::CreatePosition;

        $positions = $this->positionsInScope($scopes, $user);
        $vacancies = $this->vacanciesFor($type);

        return view('livewire.recruitment.requests.direct-open', [
            'positions' => $positions->with(['program', 'city'])->orderBy(transColumn('title'))->get(),
            'vacancies' => $vacancies,
            // cities, centres and contract types name themselves per locale;
            // programmes and departments have one name column between them.
            'cities' => City::query()->orderBy(transColumn('name'))->get(),
            'programs' => Program::query()->where('is_active', true)->orderBy('name')->get(),
            'centers' => Center::query()
                ->when(! empty($this->data['city_id']), fn ($query) => $query->where('city_id', $this->data['city_id']))
                ->orderBy(transColumn('name'))->get(),
            'departments' => Department::query()->orderBy('name')->get(),
            'jobLevels' => JobLevel::query()->orderBy('grade')->get(),
            'contractTypes' => ContractType::query()->orderBy(transColumn('name'))->get(),
            'centerRequired' => ! $newPosition || $this->centerIsRequired(),
            'steps' => app(RecruitmentDirectOpenService::class)->confirmableSteps(),
            'decisions' => [ExternalConfirmationDecision::Approved, ExternalConfirmationDecision::NotApplicable],
            'methods' => [
                ExternalConfirmationMethod::Phone, ExternalConfirmationMethod::Email,
                ExternalConfirmationMethod::Meeting, ExternalConfirmationMethod::Other,
            ],
        ]);
    }

    /**
     * Every post in the governorate this HR opens for; everything, for HR Syria.
     *
     * @return \Illuminate\Database\Eloquent\Builder<Position>
     */
    private function positionsInScope(RecruitmentScopeResolver $scopes, $user)
    {
        $positions = Position::query()->where('is_active', true);

        if ($scopes->scopeType($user) === RecruitmentScopeType::Organization) {
            return $positions;
        }

        $cityIds = $scopes->governorateCityIds($user);

        return $positions->where(function ($query) use ($cityIds): void {
            foreach ($cityIds as $cityId) {
                $query->orWhere(fn ($inner) => $inner->inCity((int) $cityId));
            }
        });
    }

    /** @return \Illuminate\Support\Collection<int, Vacancy> */
    private function vacanciesFor(int $type)
    {
        if (! in_array($type, [RecruitmentRequestType::FillVacancy, RecruitmentRequestType::CloseVacancy], true)
            || empty($this->data['position_id'])) {
            return collect();
        }

        return Vacancy::query()
            ->when($type === RecruitmentRequestType::FillVacancy, fn ($query) => $query->available())
            ->where('position_id', $this->data['position_id'])
            ->orderBy('id')
            ->get();
    }

    private function centerIsRequired(): bool
    {
        $slugs = config('recruitment.request_form.centerless_program_slugs', []);
        $program = Program::query()->find($this->data['program_id'] ?? null);

        return ($this->data['volunteer_type'] ?? null) !== 'remote'
            && ! in_array($program?->slug, $slugs, true);
    }

    private function authorizeScreen(): void
    {
        Gate::authorize('directOpen', RecruitmentRequest::class);
    }

    /**
     * Say what happened, the way the rest of the application says it.
     *
     * A toast rather than a coloured panel: the panel pushed the page down on
     * every save, and a red or green block across a form reads as part of the
     * form rather than as a remark about it.
     */
    private function toast(string $message, bool $success = true): void
    {
        $alert = LivewireAlert::title($message);

        ($success ? $alert->success() : $alert->error())
            ->toast()
            ->position('top-start')
            ->show();
    }
}
