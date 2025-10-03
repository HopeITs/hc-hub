<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Program extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $guarded = ['id'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function cities(): BelongsToMany
    {
        return $this->belongsToMany(City::class, 'positions', 'program_id', 'city_id');
    }

    public function centers(): BelongsToMany
    {
        return $this->belongsToMany(Center::class, 'positions', 'program_id', 'center_id')->distinct();
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

    public function employees(): \Illuminate\Database\Eloquent\Builder|\LaravelIdea\Helper\App\Models\_IH_Employee_QB
    {
        return Employee::query()
            ->select('employees.*')
            ->distinct()
            ->whereHas('contracts.position', function ($q) {
                $q->where('program_id', $this->id);
            });
    }

    public function manager()
    {
        return $this->employees()
            ->whereHas('contracts', function ($q) {
                $q->where('is_active', true)
                    ->whereHas('position.jobLevel', function ($qq) {
                        $qq->where('grade', 1);
                    });
            })
            ->first();
    }
}
