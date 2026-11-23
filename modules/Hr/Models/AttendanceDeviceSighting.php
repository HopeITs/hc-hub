<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * A serial number that called the receiver but is not registered: HR can add it from here.
 *
 * @property int $id
 * @property string $serial
 * @property string|null $last_ip
 * @property int $attempts
 * @property CarbonImmutable|null $last_seen_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['serial', 'last_ip', 'attempts', 'last_seen_at'])]
class AttendanceDeviceSighting extends Model
{
    /** @var array<string, mixed> */
    protected $attributes = ['attempts' => 0];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['last_seen_at' => 'datetime'];
    }
}
