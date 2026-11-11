<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\User;
use Modules\Hr\Models\Concerns\HasTranslation;

/**
 * An official holiday, a one-off grant, or the birthday holiday
 * (team-coverage-contract §6.1).
 *
 * scope national → everyone; scope projects → the program × governorate
 * pairs in holiday_project. A birthday row is only a validity window: each
 * volunteer's day is computed from their own date of birth (BirthdayHoliday).
 *
 * @property int $id
 * @property CarbonImmutable $start_date
 * @property CarbonImmutable $end_date
 * @property bool $is_recurring
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property string $name_en
 * @property string $name_ar
 * @property string $scope
 * @property string $kind
 * @property int|null $created_by
 */
#[Fillable(['name_en', 'name_ar', 'start_date', 'end_date', 'is_recurring', 'scope', 'kind', 'created_by'])]
class Holiday extends Model
{
    use HasTranslation;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_recurring' => 'boolean',
        ];
    }

    public const SCOPE_NATIONAL = 'national';

    public const SCOPE_PROJECTS = 'projects';

    public const KIND_OFFICIAL = 'official';

    public const KIND_GRANT = 'grant';

    public const KIND_BIRTHDAY = 'birthday';

    /**
     * @return HasMany<HolidayProject, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(HolidayProject::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isNational(): bool
    {
        return $this->scope === self::SCOPE_NATIONAL;
    }

    /**
     * Holidays that reach a volunteer in this program × governorate.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeApplicableTo(Builder $query, ?int $programId, ?int $cityId): Builder
    {
        return $query->where(function (Builder $q) use ($programId, $cityId) {
            $q->where('scope', self::SCOPE_NATIONAL);

            if ($programId && $cityId) {
                $q->orWhere(fn (Builder $p) => $p
                    ->where('scope', self::SCOPE_PROJECTS)
                    ->whereHas('projects', fn (Builder $link) => $link
                        ->where('program_id', $programId)
                        ->where('city_id', $cityId)));
            }
        });
    }

    /**
     * Rows that may have a date between $from and $to (recurring rows always may).
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOccurringBetween(Builder $query, CarbonImmutable $from, CarbonImmutable $to): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->where('is_recurring', true)
            ->orWhere(fn (Builder $r) => $r
                ->whereDate('start_date', '<=', $to->toDateString())
                ->whereDate('end_date', '>=', $from->toDateString())));
    }

    /**
     * @return Attribute<string|null, string|null>
     */
    protected function name(): Attribute
    {
        return $this->translatable('name');
    }

    /**
     * Holidays whose English or Arabic name contains the search.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $query->where(fn (Builder $query) => $query
            ->whereLike($query->qualifyColumn('name_en'), "%{$term}%")
            ->orWhereLike($query->qualifyColumn('name_ar'), "%{$term}%"));
    }
}
