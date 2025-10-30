<?php

namespace App\Models;

use App\Traits\HasTranslation;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LeaveType extends Model
{
    use HasFactory,HasTranslation;

    protected $fillable = [
        'id',
        'name_en',
        'name_ar',
        'base_days',
        'allowed_gender',
        'marital_status'
    ];
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->getTranslatableAttribute('name'),
            set: fn($value) => $this->setTranslatableAttribute('name', $value)
        );
    }
    public function leaveBalances()
    {
        return $this->hasMany(LeaveBalance::class , 'type_id');
    }

    public function isAnnual(): bool
    {
        return $this->id === \App\Enums\LeaveTypeCases::Annual;
    }
}
