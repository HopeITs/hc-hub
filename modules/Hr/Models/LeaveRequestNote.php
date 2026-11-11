<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * HR's note on a leave request (C3, DEC-05 revision): optional with "Seen by
 * HR", required with an objection. Visible to HR, the direct manager and the
 * requester; written only by LeaveRequestReview.
 *
 * @property int $id
 * @property int $leave_request_id
 * @property int|null $author_employee_id
 * @property string|null $author_role
 * @property string $kind
 * @property string $body
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['leave_request_id', 'author_employee_id', 'author_role', 'kind', 'body'])]
class LeaveRequestNote extends Model
{
    public const KIND_NOTE = 'note';

    public const KIND_OBJECTION = 'objection';

    /**
     * @return BelongsTo<LeaveRequest, $this>
     */
    public function leaveRequest(): BelongsTo
    {
        return $this->belongsTo(LeaveRequest::class);
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'author_employee_id');
    }
}
