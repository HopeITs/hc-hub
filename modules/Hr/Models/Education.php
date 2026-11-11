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
 * @property string $name
 * @property string $specialization
 * @property int|null $degree
 * @property int $status
 * @property string|null $notes
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['employee_id', 'name', 'specialization', 'degree', 'status', 'notes'])]
class Education extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'degree' => 'integer',
            'status' => 'integer',
        ];
    }

    protected $table = 'educations';

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
