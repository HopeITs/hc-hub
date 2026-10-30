<?php

namespace Modules\Hr\Models;

use App\Traits\HasTranslation;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class LeaveType extends Model
{
    use HasFactory,HasTranslation, LogsActivity;

    protected $fillable = [
        'name_en',
        'name_ar',
        'base_days',
        'allowed_gender',
        'marital_status',
    ];

    public function leaveBalances()
    {
        return $this->hasMany(LeaveBalance::class, 'type_id');
    }

    public function isAnnual(): bool
    {
        return $this->id === \Modules\Hr\Enums\LeaveTypeCases::Annual;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('leave_type')
            ->logOnly([
                'name',           // translatable field
                'name_en',
                'name_ar',
                'base_days',
                'allowed_gender',
                'marital_status',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->getTranslatableAttribute('name'),
            set: fn ($value) => $this->setTranslatableAttribute('name', $value),
        );
    }
}
