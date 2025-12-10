<?php

namespace App\Models;

use App\Traits\HasTranslation;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Department extends Model
{
    use HasFactory, HasTranslation, LogsActivity;

    protected $guarded = [
        'id'
    ];

    protected function description(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->getTranslatableAttribute('description'),
            set: fn($value) => $this->setTranslatableAttribute('description', $value)
        );
    }

    public function positions()
    {
        return $this->hasMany(Position::class, 'department_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('department')
            ->logOnly([
                'description',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
