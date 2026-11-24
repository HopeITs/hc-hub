<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Database\Factories\EntitlementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Hr\Models\Concerns\HasTranslation;

/**
 * An item of the Permissions Library (meeting 19/11, track 5): access a job
 * needs outside this system. Recorded and tracked here, never enforced, and
 * never a Spatie permission.
 *
 * @property int $id
 * @property string $name_en
 * @property string $name_ar
 * @property string $category
 * @property string|null $reference
 * @property int|null $mail_list_id
 * @property string|null $description
 * @property bool $is_active
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['name_en', 'name_ar', 'category', 'reference', 'mail_list_id', 'description', 'is_active'])]
class Entitlement extends Model
{
    /** @use HasFactory<EntitlementFactory> */
    use HasFactory, HasTranslation;

    /** @var list<string> */
    public const array CATEGORIES = ['database', 'telegram', 'mail_list', 'shared_drive', 'social_media', 'camera', 'it_maintenance', 'equipment', 'physical_access', 'other'];

    /** @var array<string, mixed> */
    protected $attributes = ['is_active' => true];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /**
     * @return BelongsTo<MailList, $this>
     */
    public function mailList(): BelongsTo
    {
        return $this->belongsTo(MailList::class);
    }

    /**
     * @return HasMany<PositionEntitlement, $this>
     */
    public function defaults(): HasMany
    {
        return $this->hasMany(PositionEntitlement::class);
    }

    /**
     * @return HasMany<EmployeeEntitlement, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(EmployeeEntitlement::class);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where($query->qualifyColumn('is_active'), true);
    }

    /**
     * @return Attribute<string, never>
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn (): string => (string) ($this->getTranslatableAttribute('name') ?: ($this->name_en ?: $this->name_ar)),
        );
    }
}
