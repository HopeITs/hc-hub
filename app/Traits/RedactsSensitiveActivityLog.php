<?php

namespace App\Traits;

use Spatie\Activitylog\Contracts\Activity;

/**
 * Records *that* a sensitive field changed without recording its value.
 *
 * The activity log was copying salaries, national ID numbers, home addresses,
 * dates of birth, relatives' phone numbers, and free-text notes into a table
 * with a much broader audience and a much longer life than the record itself.
 * The audit requirement is "who changed what, when" — the changed field name
 * satisfies that; the value does not, and the current value is one join away
 * for anyone actually authorized to see it.
 *
 * Usage: list the field names in $sensitiveLogAttributes and keep them out of
 * logOnly(). Their names then appear under the `sensitive_fields_changed`
 * property on the activity record.
 */
trait RedactsSensitiveActivityLog
{
    public function tapActivity(Activity $activity, string $eventName): void
    {
        $sensitive = $this->sensitiveLogAttributes ?? [];

        if ($sensitive === [] || $eventName === 'deleted') {
            return;
        }

        $changed = array_values(array_intersect($sensitive, array_keys($this->getDirty())));

        if ($changed === []) {
            return;
        }

        $properties = $activity->properties ?? collect();

        $activity->properties = $properties->put('sensitive_fields_changed', $changed);
    }
}
