<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Database\Factories\ContractTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Hr\Models\Concerns\HasTranslation;

/**
 * @property int $id
 * @property string $name_en
 * @property string $name_ar
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['name_en', 'name_ar'])]
class ContractType extends Model
{
    /** @use HasFactory<ContractTypeFactory> */
    use HasFactory, HasTranslation;

    /**
     * @return HasMany<Contract, $this>
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'contract_type_id');
    }

    public function getCategoryAttribute(): string
    {
        $map = config('contracts.map_by_id');

        return $map[$this->id] ?? 'annual';
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<ContractType>
     * @return Builder<self>
     */
    public function scopeRecruitmentOptions(Builder $query): Builder
    {
        return $query->whereIn('name_en', config('recruitment.request_form.contract_types', []));
    }

    /**
     * @return Attribute<string|null, string|null>
     */
    protected function name(): Attribute
    {
        return $this->translatable('name');
    }
}
