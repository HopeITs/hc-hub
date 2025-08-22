<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Translatable\HasTranslations;

class Project extends Model implements HasMedia
{
    use HasFactory, HasTranslations, InteractsWithMedia;

    protected $guarded = ['id'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public array $translatable = ['name', 'description'];

    public function centers()
    {
        return $this->hasMany(Center::class);
    }

    public function cities()
    {
        return $this->belongsToMany(City::class, 'centers', 'project_id', 'city_id');
    }

    public function requiredPositions()
    {
        return $this->hasMany(RequiredPosition::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')->singleFile();
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }
}
