<?php

namespace App\Models;

use App\Traits\HasTranslation;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class City extends Model
{
    use HasFactory ,HasTranslation;

    protected $fillable = [
        'id',
        'name_en',
        'name_ar',
        'code',
        'is_active'
    ];
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->getTranslatableAttribute('name'),
            set: fn($value) => $this->setTranslatableAttribute('name', $value)
        );
    }

    public function holidays() {
        return $this->morphMany(Holiday::class, 'holidayable');
    }
    public function centers()
    {
        return $this->hasMany(Center::class);
    }
    public function positions()
    {
        return $this->hasMany(Position::class);
    }
}
