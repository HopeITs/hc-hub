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

    /** Only normal draft creation; no HR master data or submitted version is written. */
    public function create(User $actor, array $input): RecruitmentRequest
    {
        // Fresh identity/assignment prevents reuse of a memoized employee contract.
        $actor = $actor->fresh();
        Gate::forUser($actor)->authorize('create', RecruitmentRequest::class);
        $data = $this->validate($input);

        return DB::transaction(function () use ($actor, $data) {
            $isNewPosition = $data['request_type'] === Type::CreatePosition;
            $targetId = $isNewPosition ? $data['direct_manager_id'] : $data['position_id'];
            $target = $this->scopes->scopeManageablePositions(Position::query(), $actor, $isNewPosition)
                ->whereKey($targetId)->lockForUpdate()->first();
            if (! $target) {
                throw ValidationException::withMessages([
                    'data.'.($isNewPosition ? 'direct_manager_id' : 'position_id') => __('recruitment.outside_tree'),
                ]);
            }
            Gate::forUser($actor)->authorize($isNewPosition ? 'createUnderPosition' : 'createForPosition', [RecruitmentRequest::class, $target]);

            $cityId = $isNewPosition ? $data['city_id'] : ($target->city_id ?? $target->center?->city_id);
            if (! $cityId) {
                throw ValidationException::withMessages(['data.position_id' => __('recruitment.missing_city')]);
            }

            if ($isNewPosition) {
                // The pickers are narrowed in the UI; this re-derives the same
                // sets so a hand-edited request gains nothing by it.
                $this->assertWithinCreationScope($actor, $data);

                if (! empty($data['center_id'])) {
                    $center = Center::query()->findOrFail($data['center_id']);
                    if (! City::isNational((int) $cityId) && $center->city_id !== null && (int) $center->city_id !== (int) $cityId) {
                        throw ValidationException::withMessages(['data.center_id' => __('recruitment.center_city_mismatch')]);
                    }
                }
            }

            if (! empty($data['vacancy_id'])) {
                // Vacancy visibility is still D30/D42, independent of D49's position selector.
                $vacancy = Vacancy::query()->visibleTo($actor)->where('position_id', $target->id)
                    ->whereKey($data['vacancy_id'])->lockForUpdate()->first();
                if (! $vacancy) {
                    throw ValidationException::withMessages(['data.vacancy_id' => __('recruitment.invalid_vacancy')]);
                }
            }

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

            // D8: audit failure is reported, and must not roll back the draft.
            // A nested transaction/savepoint also keeps the outer transaction usable.
            try {
                DB::transaction(fn () => $request->events()->create([
                    'event_type' => RecruitmentEventType::RequestCreated,
                    'to_status' => RecruitmentRequestStatus::Draft,
                    'actor_user_id' => $actor->id,
                    'actor_role' => $actor->myRole(),
                    'scope_type' => $this->scopes->scopeType($actor),
                    'scope_id' => $this->scopes->scopeType($actor) === RecruitmentScopeType::DirectReports
                        ? $this->scopes->position($actor)?->id : null,
                ]));
            } catch (Throwable $exception) {
                report($exception);
            }

            return $request;
        });
    }

    /**
     * A new position must sit at a grade junior to the requester's own.
     *
     * City and program are deliberately not checked here. D49 settled that the
     * management tree is what authorizes creation and that those two stay
     * organizational data — a national manager opening the first position in a
     * governorate he has no one in yet is ordinary growth, not an escape.
     *
     * The grade is different: it is a statement about the new position's place
     * in the hierarchy, and nobody creates a peer or a superior for himself.
     */
    private function assertWithinCreationScope(User $actor, array $data): void
    {
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
                'tech_manager_id' => ['nullable', 'integer', 'exists:positions,id'],
                'title_en' => ['nullable', 'string', 'max:255'],
                'title_ar' => ['nullable', 'string', 'max:255'],
                'qualifications' => ['nullable', 'string'],
                'responsibilities' => ['nullable', 'string'],
                'max_emp' => ['nullable', 'integer', 'min:1'],
            ];
        }

        if (in_array($type, [Type::FillVacancy, Type::IncreaseHeadcount, Type::CreatePosition], true)) {
            $rules += [
                'salary_amount' => ['nullable', 'integer', 'min:0'],
                'currency' => ['nullable', 'integer', Rule::in([Currency::SYP, Currency::USD, Currency::EUR])],
                'salary_per' => ['nullable', 'integer', Rule::in([SalaryPer::Month, SalaryPer::Hour, SalaryPer::Session])],
                'contract_type_id' => ['nullable', 'integer', 'exists:contract_types,id'],
                'finance_consulted' => ['nullable', 'boolean'],
                'detailed_explanation' => ['nullable', 'string'],
            ];
        }
        if (in_array($type, [Type::FillVacancy, Type::IncreaseHeadcount], true)) {
            $rules += ['justification' => ['nullable', 'string'], 'needed_by_date' => ['nullable', 'date_format:Y-m-d']];
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
