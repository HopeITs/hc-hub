<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Center extends Model
{
    use HasFactory;

    protected $fillable = ['id', 'name', 'city_id', 'project_id', 'address', 'is_active'];

    protected $casts = [
        'name' => 'array',
        'address' => 'array',
    ];

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function departments()
    {
        return $this->hasMany(Department::class);
    }
}
