<?php

namespace App\Models;

use App\Traits\HasTranslation;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Department extends Model
{
    use HasFactory, HasTranslation, LogsActivity;

    protected $fillable = [
        'name', 'code', 'description_en', 'description_ar', 'email',
    ];

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

    protected function description(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->getTranslatableAttribute('description'),
            set: fn ($value) => $this->setTranslatableAttribute('description', $value),
        );
    }
}
