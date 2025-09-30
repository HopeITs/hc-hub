<?php

namespace App\Models;

use App\Traits\HasTranslation;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JobLevel extends Model
{
    use HasFactory , HasTranslation;

    protected $fillable = [
        'name_en',
        'name_ar',
        'description',
        'grade',
        'email',
    ];

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->getTranslatableAttribute('name'),
            set: fn($value) => $this->setTranslatableAttribute('name', $value)
        );
    }
    public function positions()
    {
        return $this->hasMany(Position::class, 'job_level_id');
    }

}
