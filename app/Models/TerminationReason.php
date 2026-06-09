<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasTranslation;
use Illuminate\Database\Eloquent\Casts\Attribute;
class TerminationReason extends Model
{
    use HasFactory,HasTranslation;

    protected $guarded = ['id'];

       protected function title(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->getTranslatableAttribute('title'),
            set: fn ($value) => $this->setTranslatableAttribute('title', $value)
        );
    }
}
