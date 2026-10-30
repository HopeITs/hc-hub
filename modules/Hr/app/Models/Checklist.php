<?php

namespace Modules\Hr\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Checklist extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'item_id',
        'employee_id',
        'delivered_id',
        'receiver_id',
        'brand',
        'model',
        'serial',
        'delivered_at',
        'delivered_status',
        'returned_at',
        'returned_status',
        'notes',
    ];

    protected $casts = [
        'delivered_at' => 'datetime',
        'returned_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function item()
    {
        return $this->belongsTo(ChecklistItem::class, 'item_id');
    }

    public function delivered()
    {
        return $this->belongsTo(Employee::class, 'delivered_id');
    }

    public function receiver()
    {
        return $this->belongsTo(Employee::class, 'receiver_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('checklist')
            ->logOnly([
                'item_id',
                'employee_id',
                'delivered_id',
                'receiver_id',
                'brand',
                'model',
                'serial',
                'delivered_at',
                'delivered_status',
                'returned_at',
                'returned_status',
                // 'notes' is free text written by staff about a specific
                // person and is not needed to audit a state change. The
                // transition itself is recorded by ChecklistWorkflow.
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
