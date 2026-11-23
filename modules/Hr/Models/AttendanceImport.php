<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\User;

/**
 * One uploaded device report (Modules\Hr\Support\Attendance\AttendanceImporter).
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $file_name
 * @property string $source file (an uploaded report) or device (pushed by a time-clock)
 * @property int|null $device_id
 * @property CarbonImmutable|null $period_start
 * @property CarbonImmutable|null $period_end
 * @property int $punches_total
 * @property int $punches_new
 * @property int $punches_locked
 * @property int $device_numbers
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['user_id', 'file_name', 'period_start', 'period_end', 'punches_total', 'punches_new', 'punches_locked', 'device_numbers', 'source', 'device_id'])]
class AttendanceImport extends Model
{
    /** @var array<string, mixed> */
    protected $attributes = ['punches_total' => 0, 'punches_new' => 0, 'punches_locked' => 0, 'device_numbers' => 0, 'source' => 'file'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'punches_total' => 'integer',
            'punches_new' => 'integer',
            'punches_locked' => 'integer',
            'device_numbers' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<AttendancePunch, $this>
     */
    public function punches(): HasMany
    {
        return $this->hasMany(AttendancePunch::class, 'import_id');
    }
}
