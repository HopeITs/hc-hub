<?php

namespace App\Console\Commands;

use App\Models\Badge;
use App\Models\Contract;
use App\Models\Employee;
use App\Models\EmployeeNote;
use App\Models\Relative;
use Illuminate\Console\Command;
use Spatie\Activitylog\Models\Activity;

/**
 * Redacts sensitive values already written to the activity log.
 *
 * Changing the models stops *new* PII from being logged; it does nothing about
 * the history already there, which is the part an attacker or an over-broad
 * internal reader would actually reach. This rewrites those rows in place:
 * the field name and the event survive, the value is replaced with a marker,
 * so the audit trail stays intact and stays useful.
 *
 * Run once after deploying the model changes, then keep it available for any
 * field added to a $sensitiveLogAttributes list later.
 */
class PurgeSensitiveActivityLog extends Command
{
    protected $signature = 'security:purge-activity-log
        {--dry-run : Report what would be redacted without writing}
        {--chunk=500 : Rows per batch}';

    protected $description = 'Redact sensitive attribute values from historical activity log entries';

    private const REDACTED = '[redacted]';

    /**
     * Subject type => attributes whose values must not persist in the log.
     * Mirrors the $sensitiveLogAttributes lists on the models themselves.
     */
    private function map(): array
    {
        return [
            Employee::class => [
                'national_number', 'civil_registry_number', 'civil_registry_office',
                'address', 'address_en', 'address_ar', 'dob', 'actual_dob',
                'personal_phone', 'work_phone', 'personal_email', 'whatsapp_work',
                'telegram_work', 'father_name_en', 'father_name_ar',
                'mother_name_en', 'mother_name_ar', 'children', 'marital_status',
                'digital_id_token',
            ],
            Contract::class => ['salary', 'attending_notes_en', 'attending_notes_ar'],
            Badge::class => ['current_salary', 'new_salary', 'reason', 'notes'],
            Relative::class => ['name', 'phone'],
            EmployeeNote::class => ['content'],
        ];
    }

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $map = $this->map();
        $redacted = 0;
        $inspected = 0;

        Activity::query()
            ->whereIn('subject_type', array_keys($map))
            ->orderBy('id')
            ->chunkById((int) $this->option('chunk'), function ($activities) use ($map, $dryRun, &$redacted, &$inspected): void {
                foreach ($activities as $activity) {
                    $inspected++;
                    $fields = $map[$activity->subject_type] ?? [];
                    $properties = $activity->properties;
                    $changedAny = false;

                    // Spatie stores before/after snapshots under 'old' and
                    // 'attributes'; both carry the value and both are rewritten.
                    foreach (['old', 'attributes'] as $bucket) {
                        $values = $properties->get($bucket);

                        if (! is_array($values)) {
                            continue;
                        }

                        foreach ($fields as $field) {
                            if (array_key_exists($field, $values) && $values[$field] !== null && $values[$field] !== self::REDACTED) {
                                $values[$field] = self::REDACTED;
                                $changedAny = true;
                            }
                        }

                        $properties = $properties->put($bucket, $values);
                    }

                    if (! $changedAny) {
                        continue;
                    }

                    $redacted++;

                    if ($dryRun) {
                        continue;
                    }

                    // Written without touching updated_at: the audit record's
                    // own timestamps must keep reflecting the original event.
                    Activity::withoutTimestamps(
                        fn () => $activity->forceFill(['properties' => $properties])->save()
                    );
                }
            });

        $this->info(sprintf(
            '%s %d of %d activity row(s).',
            $dryRun ? 'Would redact' : 'Redacted',
            $redacted,
            $inspected,
        ));

        return self::SUCCESS;
    }
}
