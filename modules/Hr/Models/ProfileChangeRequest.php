<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\User;

/**
 * A change an employee proposed to their own profile, waiting for HR
 * (D1, profile-contract.md §5.1). Written only by ProfileChangeRequests.
 *
 * Files stay on the private disk: `file` (a certificate) and `photo` (already
 * re-encoded by SecureImageUploadService on submission). They are copied to
 * the employee's own record when HR approves, never re-encoded again.
 *
 * @property int $id
 * @property int $employee_id
 * @property string $section
 * @property array<mixed> $proposed
 * @property int $status
 * @property int|null $requested_by
 * @property int|null $decided_by
 * @property mixed|null $decided_at
 * @property string|null $reason
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['employee_id', 'section', 'proposed', 'status', 'requested_by', 'decided_by', 'decided_at', 'reason'])]
class ProfileChangeRequest extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'proposed' => 'array',
            'status' => 'integer',
            'decided_at' => 'datetime',
        ];
    }

    public const PENDING = 0;

    public const APPROVED = 1;

    public const REJECTED = 2;

    public const REPLACED = 3;

    public const SECTIONS = ['education', 'course', 'experience', 'relative', 'photo'];

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::PENDING);
    }
}
