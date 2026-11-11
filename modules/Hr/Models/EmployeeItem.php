<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $employee_id
 * @property string $item
 * @property string|null $brand
 * @property string|null $model
 * @property string|null $serial_number
 * @property string|null $status
 * @property string|null $notes
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['employee_id', 'item', 'brand', 'model', 'serial_number', 'status', 'notes'])]
class EmployeeItem extends Model {}
