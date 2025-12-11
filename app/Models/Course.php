<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Course extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, LogsActivity;

    protected $guarded = [
        'id',
    ];
    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'has_certificate' => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
    public function category()
    {
        return $this->belongsTo(CourseCategory::class, 'category_id');
    }
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('file')
            ->singleFile();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('course')
            ->logOnly([
                'employee_id',
                'issue_date',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
