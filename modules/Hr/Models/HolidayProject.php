<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\City;
use Modules\Core\Models\Program;

/**
 * One project (program × governorate) a holiday applies to.
 *
 * @property int $id
 * @property int $holiday_id
 * @property int $program_id
 * @property int $city_id
 */
#[Fillable(['holiday_id', 'program_id', 'city_id'])]
class HolidayProject extends Model
{
    public $timestamps = false;

    protected $table = 'holiday_project';

    /**
     * @return BelongsTo<Holiday, $this>
     */
    public function holiday(): BelongsTo
    {
        return $this->belongsTo(Holiday::class);
    }

    /**
     * @return BelongsTo<Program, $this>
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * @return BelongsTo<City, $this>
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
