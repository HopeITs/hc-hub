<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Core\Models\City;
use Modules\Core\Models\User;
use Modules\Hr\Actions\Recruitment\RecruitmentScopeResolver;
use Modules\Hr\Enums\RecruitmentRequestStatus;
use Modules\Hr\Enums\RecruitmentRequestType;

/**
 * @property int $id
 * @property string $request_number
 * @property int $requester_user_id
 * @property int $request_type
 * @property int $mode
 * @property int $status
 * @property int|null $scope_city_id
 * @property int|null $target_position_id
 * @property int|null $target_vacancy_id
 * @property array<mixed>|null $draft_payload_json
 * @property string|null $actual_requester_name
 * @property int|null $actual_requester_user_id
 * @property int|null $actual_requester_position_id
 * @property string|null $direct_open_reason
 * @property int|null $direct_opened_by_user_id
 * @property mixed|null $direct_opened_at
 * @property mixed|null $submitted_at
 * @property mixed|null $applied_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class RecruitmentRequest extends Model
{
    protected $guarded = ['id'];

    /**
     * @return BelongsTo<User, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_user_id');
    }

    /**
     * @return BelongsTo<City, $this>
     */
    public function scopeCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'scope_city_id');
    }

    /**
     * @return BelongsTo<Position, $this>
     */
    public function targetPosition(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'target_position_id');
    }

    /**
     * @return BelongsTo<Vacancy, $this>
     */
    public function targetVacancy(): BelongsTo
    {
        return $this->belongsTo(Vacancy::class, 'target_vacancy_id');
    }

    /**
     * @return BelongsTo<Position, $this>
     */
    public function actualRequesterPosition(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'actual_requester_position_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actualRequester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actual_requester_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function directOpenedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'direct_opened_by_user_id');
    }

    /**
     * @return HasMany<RecruitmentRequestVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(RecruitmentRequestVersion::class, 'request_id');
    }

    /**
     * @return HasOne<RecruitmentRequestVersion, $this>
     */
    public function latestVersion(): HasOne
    {
        return $this->hasOne(RecruitmentRequestVersion::class, 'request_id')
            ->ofMany('version_no', 'max');
    }

    /**
     * @return HasMany<RecruitmentWorkflowInstance, $this>
     */
    public function workflowInstances(): HasMany
    {
        return $this->hasMany(RecruitmentWorkflowInstance::class, 'request_id');
    }

    /**
     * @return HasMany<RecruitmentRequestComment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(RecruitmentRequestComment::class, 'request_id');
    }

    /**
     * @return HasMany<RecruitmentRequestExternalConfirmation, $this>
     */
    public function externalConfirmations(): HasMany
    {
        return $this->hasMany(RecruitmentRequestExternalConfirmation::class, 'recruitment_request_id');
    }

    /**
     * @return HasMany<RecruitmentRequestEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(RecruitmentRequestEvent::class, 'recruitment_request_id');
    }

    /**
     * @return HasOne<Vacancy, $this>
     */
    public function vacancy(): HasOne
    {
        return $this->hasOne(Vacancy::class, 'created_from_request_id');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeBlockingFill(Builder $query): Builder
    {
        return $query->where('request_type', RecruitmentRequestType::FillVacancy->value)
            ->where(function (Builder $statuses): void {
                $statuses->whereIn('status', [
                    RecruitmentRequestStatus::Submitted->value,
                    RecruitmentRequestStatus::UnderReview->value,
                    RecruitmentRequestStatus::ChangesRequested->value,
                    RecruitmentRequestStatus::Resubmitted->value,
                    RecruitmentRequestStatus::Approved->value,
                ])->orWhere(function (Builder $applied): void {
                    $unfilled = fn (Builder $vacancies): Builder => $vacancies
                        ->where('status', Vacancy::OPEN)->whereNull('closed_at')
                        ->whereColumn('filled_count', '<', 'needed_count');

                    $applied->where('status', RecruitmentRequestStatus::Applied->value)
                        ->where(fn (Builder $vacancies): Builder => $vacancies
                            ->whereHas('vacancy', $unfilled)->orWhereHas('targetVacancy', $unfilled));
                });
            });
    }

    protected function casts(): array
    {
        return [
            'status' => 'integer',
            'request_type' => 'integer',
            'mode' => 'integer',

            'draft_payload_json' => 'array',
            'direct_opened_at' => 'datetime',
            'submitted_at' => 'datetime',
            'applied_at' => 'datetime',
        ];
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return app(RecruitmentScopeResolver::class)->scopeRequests($query, $user);
    }
}
