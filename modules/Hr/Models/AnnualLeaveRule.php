<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int|null $days
 * @property float|null $day_per_month
 * @property int|null $years_of_volunteering
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['days', 'day_per_month', 'years_of_volunteering'])]
class AnnualLeaveRule extends Model {}
