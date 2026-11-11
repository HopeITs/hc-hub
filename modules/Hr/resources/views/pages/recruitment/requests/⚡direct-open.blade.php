<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Modules\Core\Models\Center;
use Modules\Core\Models\City;
use Modules\Core\Models\Program;
use Modules\Hr\Actions\Recruitment\RecruitmentDirectOpenService;
use Modules\Hr\Enums\ExternalConfirmationDecision;
use Modules\Hr\Enums\ExternalConfirmationMethod;
use Modules\Hr\Enums\RecruitmentRequestType;
use Modules\Hr\Livewire\Concerns\Recruitment\AuthorizesRecruitment;
use Modules\Hr\Models\ContractType;
use Modules\Hr\Models\Department;
use Modules\Hr\Models\JobLevel;
use Modules\Hr\Models\RecruitmentRequest;
use Modules\Hr\Models\Vacancy;
use Modules\Hr\Support\Recruitment\Labels;
use Modules\Hr\Support\Recruitment\RecruitmentConfig;
use Modules\Hr\Support\Recruitment\Text;

/*
 * HR opens a request for a hire already agreed outside the system. The
 * request content is the same form a manager fills; what differs is who
 * actually asked, why it skips the queue, and how each approval was
 * confirmed. The service records the approvals as given, never the facts.
 */
new #[Layout('layouts::desk'), Title('hr_recruitment.hub.titles.direct_open')] class extends Component {
    use AuthorizesRecruitment;

    /** @var array<string, mixed> */
    public array $data = [];

    /** @var array<string, mixed> */
    public array $documentation = [
        'actual_requester_user_id' => null,
        'direct_open_reason' => '',
        'confirmations' => [],
    ];

    public function mount(RecruitmentDirectOpenService $directOpen): void
    {
        $this->requirePermission('recruitment-requests.view', 'recruitment-requests.direct-open');
        Gate::authorize('directOpen', RecruitmentRequest::class);
        RecruitmentConfig::load();

        $this->data = $this->blankData(RecruitmentRequestType::FillVacancy->value);

        foreach ($directOpen->confirmableSteps() as $stepKey) {
            $this->documentation['confirmations'][$stepKey] = [
                'decision' => null,
                'confirmer_user_id' => null,
                'method' => null,
                'confirmed_at' => null,
                'not_applicable_reason' => '',
                'notes' => '',
            ];
        }
    }

    /** @return array<string, mixed> */
    private function blankData(int $type): array
    {
        return array_fill_keys(['position_id', 'direct_manager_id', 'vacancy_id', 'volunteers_required', 'city_id', 'program_id', 'center_id', 'department_id', 'job_level_id', 'recruitment_reason', 'priority', 'volunteer_type', 'currency', 'salary_per', 'contract_type_id'], null)
            + ['request_type' => $type];
    }

    public function updatedDataRequestType(): void
    {
        $this->data = $this->blankData((int) $this->data['request_type']);
        $this->resetValidation();
    }

    public function updatedDataPositionId(): void
    {
        $this->data['vacancy_id'] = null;
        $this->resetConfirmationPeople();
    }

    public function updatedDataCityId(): void
    {
        $this->data['center_id'] = null;
        $this->resetConfirmationPeople();
    }

    public function updatedDocumentationActualRequesterUserId(): void
    {
        $this->data['position_id'] = null;
        $this->data['direct_manager_id'] = null;
        $this->data['vacancy_id'] = null;
        $this->resetConfirmationPeople();
    }

    public function save(RecruitmentDirectOpenService $directOpen): void
    {
        Gate::authorize('directOpen', RecruitmentRequest::class);
        $this->resetValidation();

        try {
            $request = $directOpen->open($this->viewer(), $this->data, $this->documentation);
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                $this->addError($field, $messages[0]);
            }

            $this->toast(__('hr_recruitment.hub.check_form'), false);

            return;
        }

        $this->toast(__('hr_recruitment.direct_open.opened'));
        $this->redirectRoute('hr.recruitment.requests.show', ['requestId' => $request->id], navigate: true);
    }

    #[Computed]
    public function requesters()
    {
        return app(RecruitmentDirectOpenService::class)->requesterCandidates($this->viewer());
    }

    /** @return array<string, mixed> */
    #[Computed]
    public function options(): array
    {
        $directOpen = app(RecruitmentDirectOpenService::class);
        $type = (int) ($this->data['request_type'] ?? 0);
        $requester = $this->requesters->first(fn ($candidate): bool => (int) $candidate->getKey() === (int) ($this->documentation['actual_requester_user_id'] ?? 0));
        $steps = $directOpen->confirmableStepsForRequester($requester, $type);
        $positions = $directOpen->positionsForRequester($this->viewer(), $requester, $type)
            ->with(['program:id,name', 'city:id,name_en,name_ar', 'center:id,city_id'])
            ->orderBy(Text::transColumn('title'))
            ->get();

        $cityId = $type === RecruitmentRequestType::CreatePosition->value
            ? (filter_var($this->data['city_id'] ?? null, FILTER_VALIDATE_INT) ?: null)
            : (($target = $positions->firstWhere('id', (int) ($this->data['position_id'] ?? 0))) ? ($target->city_id ?? $target->center?->city_id) : null);

        $candidates = [];

        if ($requester !== null) {
            foreach ($steps as $stepKey) {
                $candidates[$stepKey] = $directOpen->confirmationCandidates($requester, $stepKey, $cityId);
            }
        }

        $slugs = (array) RecruitmentConfig::get('recruitment.request_form.centerless_program_slugs', []);
        $program = empty($this->data['program_id']) ? null : Program::query()->find($this->data['program_id'], ['id', 'slug']);
        $centerRequired = $type !== RecruitmentRequestType::CreatePosition->value
            || (($this->data['volunteer_type'] ?? null) !== 'remote' && ! in_array($program?->slug, $slugs, true));

        return [
            'positions' => $positions,
            'vacancies' => $type === RecruitmentRequestType::FillVacancy->value && ! empty($this->data['position_id'])
                ? Vacancy::query()->available()->where('position_id', $this->data['position_id'])->orderBy('id')->get(['id', 'position_id', 'needed_count', 'filled_count'])
                : collect(),
            'cities' => City::query()->orderBy(Text::transColumn('name'))->get(['id', 'name_en', 'name_ar']),
            'programs' => Program::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'slug']),
            'centers' => Center::query()->when(! empty($this->data['city_id']), fn ($query) => $query->where('city_id', $this->data['city_id']))
                ->orderBy(Text::transColumn('name'))->get(['id', 'name_en', 'name_ar']),
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
            'jobLevels' => JobLevel::query()->orderBy('grade')->get(['id', 'name_en', 'name_ar', 'grade']),
            'contractTypes' => ContractType::query()->recruitmentOptions()->orderBy(Text::transColumn('name'))->get(['id', 'name_en', 'name_ar']),
            'centerRequired' => $centerRequired,
            'steps' => $steps,
            'candidates' => $candidates,
            'form' => (array) RecruitmentConfig::get('recruitment.request_form', []),
        ];
    }

    public function personLabel($person): string
    {
        $position = $person->employee?->latestActiveContract()?->position;

        return ($person->employee?->name ?? $person->email).' · '.($position?->title ?? __('hr_recruitment.direct_open.position_unknown'));
    }

    private function resetConfirmationPeople(): void
    {
        foreach (array_keys($this->documentation['confirmations']) as $stepKey) {
            $this->documentation['confirmations'][$stepKey]['confirmer_user_id'] = null;
        }
    }
};
?>

@php
    $options = $this->options;
    $muted = 'text-secondary-medium dark:text-ink-muted';
    $decisions = [ExternalConfirmationDecision::Approved, ExternalConfirmationDecision::NotApplicable];
    $methods = [ExternalConfirmationMethod::Phone, ExternalConfirmationMethod::Email, ExternalConfirmationMethod::Meeting, ExternalConfirmationMethod::Other];
@endphp

<div class="mx-auto max-w-5xl space-y-6">
    <x-ui.detail-header
        :back="\App\Support\BackUrl::for(route('hr.recruitment.requests.index'))"
        :breadcrumbs="[['label' => __('hr_recruitment.hub.section')], ['label' => __('hr_recruitment.hub.titles.requests'), 'href' => route('hr.recruitment.requests.index')]]"
        :title="__('hr_recruitment.direct_open.title')"
        :subtitle="__('hr_recruitment.direct_open.hint')"
    />

    <form wire:submit="save" novalidate class="space-y-6">
        <x-ui.panel :clip="false">
            <div class="space-y-5 p-4">
                <x-ui.form-select wire:model.live="documentation.actual_requester_user_id" :label="__('hr_recruitment.direct_open.actual_requester_name')" :placeholder="__('hr_recruitment.choose')" required :help="__('hr_recruitment.direct_open.requester_help')" wrap-labels>
                    @foreach ($this->requesters as $person)
                        <option value="{{ $person->id }}">{{ $this->personLabel($person) }}</option>
                    @endforeach
                </x-ui.form-select>
                @error('actual_requester_user_id')<p class="text-sm text-status-critical">{{ $message }}</p>@enderror
                @error('actual_requester_name')<p class="text-sm text-status-critical">{{ $message }}</p>@enderror

                <x-ui.form-textarea wire:model="documentation.direct_open_reason" :label="__('hr_recruitment.direct_open.reason')" rows="3" required dir="auto" />
                @error('direct_open_reason')<p class="text-sm text-status-critical">{{ $message }}</p>@enderror
            </div>
        </x-ui.panel>

        <x-hr::recruitment.request-fields
            :data="$data" :positions="$options['positions']" :vacancies="$options['vacancies']"
            :cities="$options['cities']" :programs="$options['programs']" :centers="$options['centers']" :center-required="$options['centerRequired']"
            :departments="$options['departments']" :job-levels="$options['jobLevels']" :contract-types="$options['contractTypes']" :form="$options['form']" />

        @if ($options['steps'] !== [])
            <x-ui.panel :title="__('hr_recruitment.direct_open.confirmations')" :subtitle="__('hr_recruitment.direct_open.confirmations_hint')" :clip="false">
                <div class="space-y-4 p-4">
                    @foreach ($options['steps'] as $stepKey)
                        @php
                            $decision = (int) ($documentation['confirmations'][$stepKey]['decision'] ?? 0);
                            $people = $options['candidates'][$stepKey] ?? collect();
                            $prefix = 'documentation.confirmations.'.$stepKey;
                            $errorPrefix = 'confirmations.'.$stepKey;
                        @endphp
                        <fieldset class="rounded-md border border-panel-border p-4" wire:key="confirm-{{ $stepKey }}">
                            <legend class="px-1 text-sm font-semibold">{{ Labels::enum('WorkflowStepKey', $stepKey) }}</legend>
                            <x-ui.form-segmented wire:model.live="{{ $prefix }}.decision" :label="__('hr_recruitment.hub.decision')"
                                :options="collect($decisions)->map(fn ($case) => ['value' => $case->value, 'label' => __('hr_recruitment.direct_open.decisions.'.$case->value)])->all()" />
                            @error($errorPrefix.'.decision')<p class="mt-1 text-sm text-status-critical">{{ $message }}</p>@enderror

                            @if ($decision === ExternalConfirmationDecision::NotApplicable->value)
                                <div class="mt-4">
                                    <x-ui.form-textarea wire:model="{{ $prefix }}.not_applicable_reason" :label="__('hr_recruitment.direct_open.not_applicable_reason')" rows="2" required dir="auto" />
                                    @error($errorPrefix.'.not_applicable_reason')<p class="mt-1 text-sm text-status-critical">{{ $message }}</p>@enderror
                                </div>
                            @elseif ($decision === ExternalConfirmationDecision::Approved->value)
                                <div class="mt-4 grid gap-4 sm:grid-cols-3">
                                    <div wire:key="confirm-person-{{ $stepKey }}-{{ md5(json_encode($people->pluck('id')->all())) }}">
                                        <x-ui.form-select wire:model.live="{{ $prefix }}.confirmer_user_id" :label="__('hr_recruitment.direct_open.confirmer_name')" :placeholder="__('hr_recruitment.choose')" required
                                            :help="$people->isEmpty() ? __('hr_recruitment.direct_open.no_confirmer_candidates') : null" wrap-labels>
                                            @foreach ($people as $person)
                                                <option value="{{ $person->id }}">{{ $this->personLabel($person) }}</option>
                                            @endforeach
                                        </x-ui.form-select>
                                        @error($errorPrefix.'.confirmer_user_id')<p class="mt-1 text-sm text-status-critical">{{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <x-ui.form-select wire:model="{{ $prefix }}.method" :label="__('hr_recruitment.direct_open.method')" :placeholder="__('hr_recruitment.choose')" required>
                                            @foreach ($methods as $method)
                                                <option value="{{ $method->value }}">{{ __('hr_recruitment.direct_open.methods.'.$method->value) }}</option>
                                            @endforeach
                                        </x-ui.form-select>
                                        @error($errorPrefix.'.method')<p class="mt-1 text-sm text-status-critical">{{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <x-ui.form-input wire:model="{{ $prefix }}.confirmed_at" :label="__('hr_recruitment.direct_open.confirmed_at')" type="date" :max="now()->toDateString()" required />
                                        @error($errorPrefix.'.confirmed_at')<p class="mt-1 text-sm text-status-critical">{{ $message }}</p>@enderror
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <x-ui.form-textarea wire:model="{{ $prefix }}.notes" :label="__('hr_recruitment.direct_open.notes')" rows="2" dir="auto" />
                                </div>
                            @endif
                        </fieldset>
                    @endforeach
                </div>
            </x-ui.panel>
        @endif

        <footer class="sticky bottom-0 z-20 flex flex-wrap items-center justify-between gap-3 rounded-panel border border-panel-border bg-white px-4 py-3 shadow-card dark:bg-surface-1 dark:shadow-raised">
            <p class="min-w-0 flex-1 basis-64 text-sm {{ $muted }}">{{ __('hr_recruitment.direct_open.hint') }}</p>
            <x-ui.button type="submit" tone="primary" icon="bolt" wire:loading.attr="disabled" wire:target="save">{{ __('hr_recruitment.direct_open.open') }}</x-ui.button>
        </footer>
    </form>
</div>
