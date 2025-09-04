<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class City extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'name_en',
        'name_ar',
        'code',
        'is_active'
    ];

    public function centers()
    {
        return $this->hasMany(Center::class);
    }
    public function positions()
    {
        return $this->hasMany(Position::class);
    }
}
