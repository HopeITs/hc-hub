<?php

namespace Modules\Hr\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ChecklistItem extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'order',
    ];

    public function checklist()
    {
        return $this->belongsTo(Checklist::class);
    }

    public function defaultDeliverable()
    {
        return $this->hasMany(DefaultDeliverable::class, 'item_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('checklist_item')
            ->logOnly([
                'name',
                'order',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
