<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One punch on a device: the device number, the employee once known, and when.
 *
 * @property int $id
 * @property int $import_id
 * @property string $device_no
 * @property int|null $employee_id
 * @property CarbonImmutable $punched_at
 * @property CarbonImmutable|null $created_at
 */
#[Fillable(['import_id', 'device_no', 'employee_id', 'punched_at'])]
class AttendancePunch extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['punched_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
