<?php

namespace App\Models;

use App\Traits\HasTranslation;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Department extends Model
{
    use HasFactory , HasTranslation;

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
        return $this->hasMany(Position::class , 'department_id');
    }

}
