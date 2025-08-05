<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Translatable\HasTranslations;


class Project extends Model implements HasMedia
{
    use HasFactory, HasTranslations,InteractsWithMedia;

    protected $fillable = [
        'id',
        'name',
        'description',
        'is_active',
    ];

    public array $translatable = ['name', 'description'];

    public function centers()
    {
        return $this->hasMany(Center::class);
    }

    public function requiredPositions()
    {
        return $this->hasMany(RequiredPosition::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')->singleFile();
    }

}
