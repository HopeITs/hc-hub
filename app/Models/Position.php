<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Position extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'max_emp'   => 'integer',
        'exist_emp' => 'integer',
        'is_active' => 'boolean',
    ];

    public function contracts(): \Illuminate\Database\Eloquent\Relations\HasMany|Position
    {
        return $this->hasMany(Contract::class, 'position_id');
    }

    public function center(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Center::class, 'center_id');
    }
    public function program(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Program::class, 'program_id');
    }
    public function city(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function jobLevel(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(JobLevel::class, 'job_level_id');
    }
    public function department(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }
}
