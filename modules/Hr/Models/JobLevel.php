<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Database\Factories\JobLevelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Hr\Models\Concerns\HasTranslation;

/**
 * @property int $id
 * @property string $name_en
 * @property string $name_ar
 * @property string|null $description
 * @property int|null $grade
 * @property string|null $email
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['name_en', 'name_ar', 'description', 'grade', 'email'])]
class JobLevel extends Model
{
    /** @use HasFactory<JobLevelFactory> */
    use HasFactory, HasTranslation;

    /**
     * Job levels have no slug, so a level is identified by its grade — the same
     * convention config/recruitment.php uses (3 = Project Manager).
     */
    public const PROJECT_MANAGER_GRADE = 3;

    /**
     * @return HasMany<Position, $this>
     */
    public function positions(): HasMany
    {
        return $this->hasMany(Position::class, 'job_level_id');
    }

    /**
     * @return Attribute<string|null, string|null>
     */
    protected function name(): Attribute
    {
        return $this->translatable('name');
    }
}
