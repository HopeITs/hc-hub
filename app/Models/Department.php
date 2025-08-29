<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Translatable\HasTranslations;

class Department extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'name',
        'description',
        'email'
    ];
    public $translatable = ['description'];

    public function positions()
    {
        return $this->hasMany(Position::class , 'department_id');
    }

}
