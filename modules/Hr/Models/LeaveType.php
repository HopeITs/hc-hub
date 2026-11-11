<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Database\Factories\LeaveTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Hr\Enums\LeaveTypeCases;
use Modules\Hr\Models\Concerns\HasTranslation;

/**
 * @property int $id
 * @property string $name_en
 * @property string $name_ar
 * @property int $base_days
 * @property int|null $allowed_gender
 * @property int|null $marital_status
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['name_en', 'name_ar', 'base_days', 'allowed_gender', 'marital_status'])]
class LeaveType extends Model
{
    /** @use HasFactory<LeaveTypeFactory> */
    use HasFactory, HasTranslation;

    /**
     * @return HasMany<LeaveBalance, $this>
     */
    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class, 'type_id');
    }

    public function isAnnual(): bool
    {
        return $this->id === LeaveTypeCases::Annual;
    }

    /**
     * @return Attribute<string|null, string|null>
     */
    protected function name(): Attribute
    {
        return $this->translatable('name');
    }
}
