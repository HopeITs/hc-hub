<?php

declare(strict_types=1);

namespace Modules\Hr\Support\Recruitment;

use App\Notifications\TracksActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Log;

/**
 * Recruitment audit lines. A subject the hub activity feed tracks (one in its
 * morph map that records activity) writes its line there; any other subject
 * goes to the application log with the same description, subject, actor and
 * properties.
 */
class ActivityLogger
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public function log(string $description, ?Model $subject = null, ?Model $causer = null, array $properties = [], ?string $logName = null): void
    {
        if ($subject instanceof TracksActivity && in_array($subject::class, Relation::morphMap(), true)) {
            $subject->recordActivity($subject->getMorphClass().'.updated', properties: ['note' => $description, 'notify' => false]);

            return;
        }

        Log::info('hr.recruitment.activity', [
            'description' => $description,
            'log' => $logName,
            'subject' => $subject === null ? null : class_basename($subject).':'.$subject->getKey(),
            'actor' => ($causer ?? auth()->user())?->getKey(),
            'properties' => $properties,
        ]);
    }
}
