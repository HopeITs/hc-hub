<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Translatable\HasTranslations;

class Program extends Model implements HasMedia
{
    use HasFactory, HasTranslations, InteractsWithMedia;

    protected $guarded = ['id'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public array $translatable = ['description'];



    public function cities(): BelongsToMany
    {
        return $this->belongsToMany(City::class, 'positions', 'program_id', 'city_id');
    }

    public function centers(): BelongsToMany
    {
        return $this->belongsToMany(Center::class, 'positions', 'program_id', 'center_id');
    }
    public function jobLevels(): BelongsToMany
    {
        return $this->belongsToMany(JobLevel::class, 'positions', 'program_id', 'job_level_id');
    }
    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'positions', 'program_id', 'department_id');
    }
    public function positions()
    {
        return $this->hasMany(Position::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')
            ->useFallbackUrl('https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&color=be1f24&background=FEE2E2')
            ->singleFile();
    }

    public function contracts()
    {
        return $this->hasManyThrough(Contract::class , Position::class)->where('contracts.is_active', true);
    }
}
