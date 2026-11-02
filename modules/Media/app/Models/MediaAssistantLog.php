<?php

namespace Modules\Media\Models;

use App\Models\City;
use App\Models\Program;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Hr\Models\Employee;

/** One sentence typed to the request assistant, and how it went. */
class MediaAssistantLog extends Model
{
    protected $fillable = [
        'employee_id', 'text', 'is_understood', 'service', 'type', 'answers', 'program_id', 'city_id',
        'needed_on', 'asked', 'media_request_id', 'final_service', 'final_type', 'is_corrected', 'lesson_id',
    ];

    protected $casts = [
        'is_understood' => 'boolean',
        'is_corrected' => 'boolean',
        'answers' => 'array',
        'asked' => 'array',
        'needed_on' => 'date',
    ];

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /** @return BelongsTo<Program, $this> */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /** @return BelongsTo<City, $this> */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /** @return BelongsTo<MediaAssistantLesson, $this> */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(MediaAssistantLesson::class);
    }

    /** @return BelongsTo<MediaRequest, $this> */
    public function mediaRequest(): BelongsTo
    {
        return $this->belongsTo(MediaRequest::class);
    }
}
