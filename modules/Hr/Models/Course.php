<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $employee_id
 * @property int|null $category_id
 * @property string $title
 * @property string|null $organization
 * @property int|null $place
 * @property string|null $granted_by
 * @property string|null $notes
 * @property mixed|null $start_date
 * @property mixed|null $end_date
 * @property bool $has_certificate
 * @property string|null $duration
 * @property string|null $cost
 * @property int $status
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['employee_id', 'category_id', 'title', 'organization', 'place', 'granted_by', 'notes', 'start_date', 'end_date', 'has_certificate', 'duration', 'cost', 'status'])]
class Course extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'has_certificate' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsTo<CourseCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(CourseCategory::class, 'category_id');
    }
}
