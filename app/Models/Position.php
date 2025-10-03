<?php

namespace App\Models;

use App\Traits\HasTranslation;
use Illuminate\Console\Prohibitable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Position extends Model
{
    use HasFactory , HasTranslation;

    protected $guarded = ['id'];

    protected $casts = [
        'max_emp' => 'integer',
        'exist_emp' => 'integer',
        'is_active' => 'boolean',
    ];
    protected function title(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->getTranslatableAttribute('title'),
            set: fn($value) => $this->setTranslatableAttribute('title', $value)
        );
    }
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'position_id');
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class, 'center_id');
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class, 'program_id');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function jobLevel(): BelongsTo
    {
        return $this->belongsTo(JobLevel::class, 'job_level_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function techTeam(): HasMany
    {
        return $this->hasMany(Position::class, 'tech_manager_id');
    }

    public function directTeam(): HasMany
    {
        return $this->hasMany(Position::class, 'direct_manager_id');
    }

    public function techManager(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'tech_manager_id');
    }

    public function ActiveContracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'position_id')->where('is_active', 1);
    }

    public function directManager(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'direct_manager_id');
    }

    public function employee(): Employee
    {
        return $this->activeContracts()->first()->employee;
    }
}
