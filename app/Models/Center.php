<?php

namespace App\Models;

use App\Traits\HasTranslation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Center extends Model
{
    use HasFactory , HasTranslation;

    protected $guarded = ['id'];



    public function getOpenAtFormattedAttribute(): string
    {
        return Carbon::createFromFormat('H:i:s', $this->open_at)->format('H:i');
    }

    public function getCloseAtFormattedAttribute(): string
    {
        return Carbon::createFromFormat('H:i:s', $this->close_at)->format('H:i');
    }

    protected function address(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->getTranslatableAttribute('address'),
            set: fn($value) => $this->setTranslatableAttribute('address', $value)
        );
    }
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }


    public function positions()
    {
        return $this->hasMany(Position::class);
    }

    public function contracts()
    {
        return $this->hasManyThrough(Contract::class, Position::class);
    }


}
