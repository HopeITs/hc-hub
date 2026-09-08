<?php

namespace App\Models;

use App\Traits\HasTranslation;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class City extends Model
{
    use HasFactory, HasTranslation, LogsActivity;

    /**
     * Slug of the nationwide "city".
     *
     * Syria is not a city beside Aleppo or Homs — it is the headquarters scope
     * that sits above all of them. A contract issued against it may be held by
     * an employee from any city, so the employee dropdown and the city
     * consistency check both drop their city filter for this one row.
     */
    public const NATIONAL_SLUG = 'SYR';

    protected $fillable = [
        'name_en',
        'name_ar',
        'code',
        'is_active',
    ];

    /**
     * Is $cityId the nationwide scope?
     *
     * Resolved by slug rather than by a hard-coded id, because the ids are
     * assigned by whichever order CitySeeder happened to run in.
     */
    public static function isNational(?int $cityId): bool
    {
        return $cityId !== null
            && static::query()
                ->whereKey($cityId)
                ->where('slug', self::NATIONAL_SLUG)
                ->exists();
    }

    public function holidays()
    {
        return $this->morphMany(Holiday::class, 'holidayable');
    }

    public function centers()
    {
        return $this->hasMany(Center::class);
    }

    public function positions()
    {
        return $this->hasMany(Position::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('city')
            ->logOnly([
                'name',
                'name_en',
                'name_ar',
                'code',
                'is_active',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->getTranslatableAttribute('name'),
            set: fn ($value) => $this->setTranslatableAttribute('name', $value),
        );
    }
}
