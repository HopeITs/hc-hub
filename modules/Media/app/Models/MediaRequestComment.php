<?php

namespace Modules\Media\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Hr\Models\Employee;

class MediaRequestComment extends Model
{
    protected $fillable = ['media_request_id', 'employee_id', 'body'];

    /** @return BelongsTo<MediaRequest, $this> */
    public function mediaRequest(): BelongsTo
    {
        return $this->belongsTo(MediaRequest::class);
    }

    /** @return BelongsTo<Employee, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
