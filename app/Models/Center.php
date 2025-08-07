<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Center extends Model
{
    use HasFactory;

    protected $fillable = ['id', 'name', 'city_id', 'project_id', 'address', 'is_active'];

    protected $casts = [
        'name' => 'array',
        'address' => 'array',
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function positions(){
        return $this->hasMany(Position::class);
    }

    public function contracts()
    {
        return $this->hasManyThrough(Contract::class, Position::class);
    }


}
