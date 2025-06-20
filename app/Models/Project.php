<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'name',
        'description',
        'is_active',
    ];

    public function centers()
    {
        return $this->hasMany(Center::class);
    }

    public function requiredPosition()
    {
        return $this->hasMany(RequiredPosition::class);
    }

}
