<?php

namespace Modules\Hr\Models;

use App\Models\City;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class DefaultDeliverable extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'employee_id',
        'city_id',
        'item_id',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function item()
    {
        return $this->belongsTo(ChecklistItem::class, 'item_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('default_deliverable')
            ->logOnly([
                'employee_id',
                'city_id',
                'item_id',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
