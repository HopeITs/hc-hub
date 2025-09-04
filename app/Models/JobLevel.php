<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JobLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_en',
        'name_ar',
        'description',
        'grade',
        'email',
    ];
    public function positions()
    {
        return $this->hasMany(Position::class, 'job_level_id');
    }

}
