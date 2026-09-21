<?php

namespace App\Services;

use App\Enums\Currency;
use App\Enums\RecruitmentEventType;
use App\Enums\RecruitmentRequestMode;
use App\Enums\RecruitmentRequestStatus;
use App\Enums\RecruitmentRequestType as Type;
use App\Enums\RecruitmentScopeType;
use App\Enums\SalaryPer;
use App\Models\Center;
use App\Models\City;
use App\Models\Position;
use App\Models\RecruitmentRequest;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class RecruitmentRequestDraftService
{
    public function __construct(private readonly RecruitmentScopeResolver $scopes) {}

    /** Recheck mutable organizational assignments and vacancy availability at submit time. */
    public function assertCurrentScope(User $actor, RecruitmentRequest $request): void
    {
        $data = $this->validate((array) $request->draft_payload_json);
        [$target] = $this->resolveTarget($actor->fresh(), $data);
        if (($data['request_type'] !== Type::CreatePosition && (int) $target->id !== (int) $request->target_position_id)
            || (int) ($data['vacancy_id'] ?? 0) !== (int) $request->target_vacancy_id) {
            throw ValidationException::withMessages(['data.vacancy_id' => __('recruitment.invalid_vacancy')]);
        }
    }

    /** Only normal draft creation; no HR master data or submitted version is written. */
    public function create(User $actor, array $input): RecruitmentRequest
    {
        // Fresh identity/assignment prevents reuse of a memoized employee contract.
        $actor = $actor->fresh();
        Gate::forUser($actor)->authorize('create', RecruitmentRequest::class);
        $data = $this->validate($input);

        return DB::transaction(function () use ($actor, $data) {
            $isNewPosition = $data['request_type'] === Type::CreatePosition;
            [$target, $cityId] = $this->resolveTarget($actor, $data);

            $request = RecruitmentRequest::query()->create([
                'request_number' => 'RR-'.Str::ulid(),
                'requester_user_id' => $actor->id,
                'request_type' => $data['request_type'],
                'mode' => RecruitmentRequestMode::Normal,
                'status' => RecruitmentRequestStatus::Draft,
                'scope_city_id' => $cityId,
                'target_position_id' => $isNewPosition ? null : $target->id,
                'target_vacancy_id' => $data['vacancy_id'] ?? null,
                'draft_payload_json' => $data,
            ]);

            $this->recordEvent($request, $actor, [
                'event_type' => RecruitmentEventType::RequestCreated,
                'to_status' => RecruitmentRequestStatus::Draft,
            ]);

            return $request;
        });
    }

    /**
     * Edit a draft (T2) or a request sent back for changes (T10).
     *
     * The same validation and the same scope checks as creation, because the
     * target may be swapped for one the requester was never allowed to touch.
     * Ownership and status are re-read under a lock so an edit cannot land on a
     * request that has meanwhile been submitted or decided.
     */
    public function update(User $actor, RecruitmentRequest $request, array $input): RecruitmentRequest
    {
        $actor = $actor->fresh();
        Gate::forUser($actor)->authorize('update', $request);
        $data = $this->validate($input);

        return DB::transaction(function () use ($actor, $request, $data) {
            $fresh = RecruitmentRequest::query()->whereKey($request->getKey())->lockForUpdate()->firstOrFail();

            if (! in_array((int) $fresh->status, [RecruitmentRequestStatus::Draft, RecruitmentRequestStatus::ChangesRequested], true)) {
                throw ValidationException::withMessages(['request' => __('recruitment.not_editable')]);
            }

            if ((int) $fresh->requester_user_id !== (int) $actor->getKey()) {
                throw ValidationException::withMessages(['request' => __('recruitment.not_your_request')]);
            }

            // The type is fixed once the request exists: changing it would make
            // the draft's own fields meaningless.
            if ((int) $data['request_type'] !== (int) $fresh->request_type) {
                throw ValidationException::withMessages(['data.request_type' => __('recruitment.type_is_fixed')]);
            }

            [$target, $cityId] = $this->resolveTarget($actor, $data);

            $fresh->forceFill([
                'scope_city_id' => $cityId,
                'target_position_id' => $data['request_type'] === Type::CreatePosition ? null : $target->getKey(),
                'target_vacancy_id' => $data['vacancy_id'] ?? null,
                'draft_payload_json' => $data,
            ])->save();

            $this->recordEvent($fresh, $actor, [
                'event_type' => RecruitmentEventType::RequestEdited,
                'to_status' => (int) $fresh->status,
            ]);

            return $fresh;
        });
    }

    /**
     * Resolve and lock the position this request is about, and its governorate.
     *
     * Shared by create and update because an edit can swap the target for one
     * the requester never had: the tree is re-checked every time, not once.
     *
     * @return array{0: Position, 1: int}
     */
    private function resolveTarget(User $actor, array &$data): array
    {
        $isNewPosition = $data['request_type'] === Type::CreatePosition;
        $targetId = $isNewPosition ? $data['direct_manager_id'] : $data['position_id'];

        $targets = $isNewPosition
            ? $this->scopes->scopeProposedManagers(Position::query(), $actor)
            : $this->scopes->scopeManageablePositions(Position::query(), $actor);
        $target = $targets
            ->whereKey($targetId)->lockForUpdate()->first();

        if (! $target) {
            throw ValidationException::withMessages([
                'data.'.($isNewPosition ? 'direct_manager_id' : 'position_id') => __('recruitment.outside_tree'),
            ]);
        }

        Gate::forUser($actor)->authorize(
            $isNewPosition ? 'createUnderPosition' : 'createForPosition',
            [RecruitmentRequest::class, $target],
        );

        $cityId = $isNewPosition ? $data['city_id'] : ($target->city_id ?? $target->center?->city_id);

        if (! $cityId) {
            throw ValidationException::withMessages(['data.position_id' => __('recruitment.missing_city')]);
        }

        if ($isNewPosition) {
            $this->assertWithinCreationScope($actor, $data);

            if (! empty($data['center_id'])) {
                $center = Center::query()->findOrFail($data['center_id']);
                if (! City::isNational((int) $cityId) && $center->city_id !== null && (int) $center->city_id !== (int) $cityId) {
                    throw ValidationException::withMessages(['data.center_id' => __('recruitment.center_city_mismatch')]);
                }
            }
        }

        if ($data['request_type'] === Type::FillVacancy) {
            if (! $target->is_active || ! $target->canAssign()) {
                throw ValidationException::withMessages(['data.position_id' => __('recruitment.no_available_vacancy')]);
            }
            $available = $target->vacancies()->available()->orderBy('id')->lockForUpdate()->get();
            if ($available->isEmpty()) {
                throw ValidationException::withMessages(['data.position_id' => __('recruitment.no_available_vacancy')]);
            }
            if (! empty($data['vacancy_id']) && ! $available->contains('id', (int) $data['vacancy_id'])) {
                throw ValidationException::withMessages(['data.vacancy_id' => __('recruitment.invalid_vacancy')]);
            }
            if ($available->count() === 1) {
                $data['vacancy_id'] = $available->first()->id;
            } elseif (empty($data['vacancy_id'])) {
                throw ValidationException::withMessages(['data.vacancy_id' => __('recruitment.choose_available_vacancy')]);
            }
        } elseif (! empty($data['vacancy_id'])) {
            // Vacancy visibility is still D30/D42, independent of D49's position selector.
            $vacancy = Vacancy::query()->visibleTo($actor)->where('position_id', $target->id)
                ->whereKey($data['vacancy_id'])->lockForUpdate()->first();

            if (! $vacancy) {
                throw ValidationException::withMessages(['data.vacancy_id' => __('recruitment.invalid_vacancy')]);
            }
        }

        return [$target, (int) $cityId];
    }

    /** D8: a failed audit write is reported and must not roll back the request. */
    private function recordEvent(RecruitmentRequest $request, User $actor, array $attributes): void
    {
        // A nested transaction/savepoint also keeps the outer transaction usable.
        try {
            DB::transaction(fn () => $request->events()->create(array_merge([
                'actor_user_id' => $actor->id,
                'actor_role' => $actor->myRole(),
                'scope_type' => $this->scopes->scopeType($actor),
                'scope_id' => $this->scopes->scopeType($actor) === RecruitmentScopeType::DirectReports
                    ? $this->scopes->position($actor)?->id : null,
            ], $attributes)));
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    /** Organizational consistency supplements, and never widens, the tree. */
    private function assertWithinCreationScope(User $actor, array $data): void
    {
        $cityId = (int) $data['city_id'];
        $programId = empty($data['program_id']) ? null : (int) $data['program_id'];
        $checks = [
            'city_id' => $this->scopes->scopeCreatableCities(City::query(), $actor)->whereKey($cityId)->exists(),
            'program_id' => $programId === null
                ? ! $this->scopes->hasProgramCreationScope($actor)
                : $this->scopes->scopeCreatablePrograms(\App\Models\Program::query(), $actor)->whereKey($programId)->exists(),
            'center_id' => empty($data['center_id']) || $this->scopes
                ->scopeCreatableCenters(Center::query(), $actor, $cityId, $programId)
                ->whereKey($data['center_id'])->exists(),
        ];
        foreach ($checks as $field => $valid) {
            if (! $valid) {
                throw ValidationException::withMessages(['data.'.$field => __('recruitment.outside_organization')]);
            }
        }

        if (empty($data['job_level_id'])) {
            return;
        }

        if (! in_array((int) $data['job_level_id'], $this->scopes->creatableJobLevelIds($actor), true)) {
            throw ValidationException::withMessages([
                'data.job_level_id' => __('recruitment.job_level_not_junior'),
            ]);
        }
    }

    /** Drafts may be incomplete, but scope/IDs and any supplied data must be valid. */
    private function validate(array $input): array
    {
        $type = Validator::make(['data' => $input], [
            'data.request_type' => ['required', 'integer', Rule::in(array_values(array_diff(Type::getValues(), [Type::Unknown])))],
        ])->validate()['data']['request_type'];
        $type = (int) $type;
        $rules = ['request_type' => ['required', 'integer']];
        $rules['position_id'] = ['required', 'integer'];

        if ($type === Type::CreatePosition) {
            unset($rules['position_id']);
            $rules += [
                'direct_manager_id' => ['required', 'integer'],
                'city_id' => ['required', 'integer', 'exists:cities,id'],
                'program_id' => ['nullable', 'integer', 'exists:programs,id'],
                'center_id' => ['nullable', 'integer', 'exists:centers,id'],
                'department_id' => ['nullable', 'integer', 'exists:departments,id'],
                'job_level_id' => ['nullable', 'integer', 'exists:job_levels,id'],
                'title_en' => ['nullable', 'string', 'max:255'],
                'title_ar' => ['nullable', 'string', 'max:255'],
                'qualifications' => ['nullable', 'string'],
                'responsibilities' => ['nullable', 'string'],
                'max_emp' => ['nullable', 'integer', 'min:1'],
            ];
        }

        // The three types that end in somebody being hired share the form the
        // paper request asks for: why, when, how long, how often and how paid.
        if (in_array($type, [Type::FillVacancy, Type::IncreaseHeadcount, Type::CreatePosition], true)) {
            $form = config('recruitment.request_form');

            $rules += [
                'recruitment_reason' => ['nullable', 'string', Rule::in($form['recruitment_reasons'])],
                'other_reason' => ['nullable', 'string', 'max:1000'],
                'priority' => ['nullable', 'string', Rule::in($form['priorities'])],
                'volunteer_type' => ['nullable', 'string', Rule::in($form['volunteer_types'])],
                'weekly_hours' => ['nullable', 'integer', 'min:1', 'max:168'],
                'volunteer_period_start_date' => ['nullable', 'date_format:Y-m-d'],
                'volunteer_period_end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:data.volunteer_period_start_date'],
                'needed_by_date' => ['nullable', 'date_format:Y-m-d'],
                'salary_amount' => ['nullable', 'integer', 'min:0'],
                'currency' => ['nullable', 'integer', Rule::in([Currency::SYP, Currency::USD, Currency::EUR])],
                'salary_per' => ['nullable', 'integer', Rule::in([SalaryPer::Month, SalaryPer::Hour, SalaryPer::Session])],
                'contract_type_id' => ['nullable', 'integer', 'exists:contract_types,id'],
                'detailed_explanation' => ['nullable', 'string'],
            ];
        }
        if (in_array($type, [Type::FillVacancy, Type::CloseVacancy], true)) {
            $rules['vacancy_id'] = [$type === Type::CloseVacancy ? 'required' : 'nullable', 'integer'];
        }
        if ($type === Type::IncreaseHeadcount) {
            $rules['additional_count'] = ['nullable', 'integer', 'min:1'];
        }
        if ($type === Type::DecreaseHeadcount) {
            $rules['reduce_by'] = ['nullable', 'integer', 'min:1'];
        }
        if (in_array($type, [Type::DecreaseHeadcount, Type::ClosePosition, Type::CloseVacancy], true)) {
            $rules['reason'] = ['nullable', 'string'];
        }
        if (in_array($type, [Type::DecreaseHeadcount, Type::ClosePosition], true)) {
            $rules['effective_date'] = ['nullable', 'date_format:Y-m-d'];
        }

        // Strip fields for other request types and all caller-supplied internal state.
        $input = array_intersect_key($input, $rules);
        $input = array_map(fn ($value) => $value === '' ? null : $value, $input);
        $prefixed = [];
        foreach ($rules as $field => $rule) {
            $prefixed['data.'.$field] = $rule;
        }
        $data = Validator::make(['data' => $input], $prefixed)->validate()['data'];
        $data['request_type'] = $type;

        return $data;
    }
}
