<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Translatable\HasTranslations;

class City extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'id',
        'name',
        'code',
        'is_active'
    ];

    public array $translatable = ['name'];

    public function centers()
    {
        return $this->hasMany(Center::class);
    }
}
