<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Recruitment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Recruitment audit lines. The old app wrote them to its own activity log;
 * the hub activity feed takes only subjects in its morph map, so until the
 * recruitment models are added there these lines go to the application log
 * with the same description, subject, actor and properties.
 */
class ActivityLogger
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public function log(string $description, ?Model $subject = null, ?Model $causer = null, array $properties = [], ?string $logName = null): void
    {
        Log::info('hr.recruitment.activity', [
            'description' => $description,
            'log' => $logName,
            'subject' => $subject === null ? null : class_basename($subject).':'.$subject->getKey(),
            'actor' => ($causer ?? auth()->user())?->getKey(),
            'properties' => $properties,
        ]);
    }
}
