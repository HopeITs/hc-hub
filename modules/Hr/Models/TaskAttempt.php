<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One attempt at one of the four X2 tasks (X2-I). Anonymous by construction:
 * the table has no column that points at a person or an HR record.
 *
 * @property int $id
 * @property string $attempt_uuid
 * @property string $task
 * @property string $role_context
 * @property string $locale
 * @property mixed $started_at
 * @property mixed|null $finished_at
 * @property int|null $duration_ms
 * @property string|null $outcome
 * @property bool $had_validation_error
 */
class TaskAttempt extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'duration_ms' => 'integer',
            'had_validation_error' => 'boolean',
        ];
    }
}
