<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Course extends Model implements HasMedia
{
    use HasFactory , InteractsWithMedia;

    protected $guarded = [
        'id',
    ];
    protected $casts = [
        'issue_date' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('file')
            ->singleFile();
    }
}
