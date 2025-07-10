<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Translatable\HasTranslations;


class Project extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'id',
        'name',
        'description',
        'is_active',
    ];

    public array $translatable = ['name', 'description'];

    public function centers()
    {
        return $this->hasMany(Center::class);
    }

    public function requiredPosition()
    {
        return $this->hasMany(RequiredPosition::class);
    }

}
