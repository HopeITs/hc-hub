<?php

namespace Modules\Hr\Models;

use Modules\Hr\Enums\AttachmentCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Attachment extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, LogsActivity;

    protected $fillable = [
        'employee_id',
        'name',
        'category',
    ];

    protected $casts = [
        'category' => AttachmentCategory::class,
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('file')->singleFile();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('attachment')
            ->logOnly([
                'employee_id',
                'name',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
