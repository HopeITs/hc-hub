<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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

    public function centers()
    {
        return $this->hasMany(Center::class);
    }

    public function cities()
    {
        return $this->belongsToMany(City::class, 'centers', 'program_id', 'city_id');
    }

    public function requiredPositions()
    {
        return $this->hasMany(RequiredPosition::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')
            ->useFallbackUrl('https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&color=be1f24&background=FEE2E2')
            ->singleFile();
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }
}
