<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Fills the inbox columns of the notifications HR wrote before the hub inbox
 * existed: the module (bell chips and mutes), kind and priority, the subject
 * the row is about, and the text inbox search matches.
 *
 * `type` is left exactly as stored (`App\Notifications\X` from before the HR
 * move, `Modules\Hr\Notifications\X` after it); it is matched here on the
 * class basename, so both spellings get the same values. The mapping is
 * frozen on purpose: a migration must keep meaning the same thing even after
 * the notification classes change. Re-running it only touches rows whose
 * module is still empty.
 */
return new class extends Migration
{
    /** @var array<string, array{module: string, kind: string, priority: string}> class basename => inbox columns */
    private const MAP = [
        'ActionLeaveBalance' => ['module' => 'leaves', 'kind' => 'action', 'priority' => 'info'],
        'ActionLeaveRequest' => ['module' => 'leaves', 'kind' => 'action', 'priority' => 'info'],
        'HRSawLeaveRequest' => ['module' => 'leaves', 'kind' => 'action', 'priority' => 'info'],
        'LeaveDecisionReminder' => ['module' => 'leaves', 'kind' => 'alert', 'priority' => 'important'],
        'LeaveRequestObjected' => ['module' => 'leaves', 'kind' => 'action', 'priority' => 'important'],
        'NewLeaveRequest' => ['module' => 'leaves', 'kind' => 'action', 'priority' => 'important'],
        'ContractsAutoRenewedSummary' => ['module' => 'contracts', 'kind' => 'digest', 'priority' => 'info'],
        'ContractsExpiringAutoRenewWarning' => ['module' => 'contracts', 'kind' => 'alert', 'priority' => 'important'],
        'CenterClosed' => ['module' => 'centers', 'kind' => 'action', 'priority' => 'info'],
        'NewCenterCreated' => ['module' => 'centers', 'kind' => 'action', 'priority' => 'info'],
        'ChecklistAssigned' => ['module' => 'checklists', 'kind' => 'action', 'priority' => 'info'],
        'FolderShared' => ['module' => 'archive', 'kind' => 'action', 'priority' => 'info'],
        'NewEmployeeWelcome' => ['module' => 'employees', 'kind' => 'action', 'priority' => 'info'],
        'ProfileChangeDecided' => ['module' => 'employees', 'kind' => 'action', 'priority' => 'info'],
        'DigitalIdReportedLost' => ['module' => 'employees', 'kind' => 'action', 'priority' => 'important'],
    ];

    public function up(): void
    {
        DB::table('notifications')
            ->whereNull('module')
            ->select(['id', 'type', 'data'])
            ->lazyById(200, 'id')
            ->each(function (object $row): void {
                $data = json_decode((string) $row->data, true);
                $data = is_array($data) ? $data : [];
                $columns = self::MAP[class_basename((string) $row->type)] ?? ['module' => 'hub', 'kind' => 'action', 'priority' => 'info'];
                $subject = is_array($data['subject'] ?? null) ? $data['subject'] : [];

                DB::table('notifications')->where('id', $row->id)->update([
                    ...$columns,
                    'subject_type' => is_string($subject['type'] ?? null) ? $subject['type'] : null,
                    'subject_id' => is_numeric($subject['id'] ?? null) ? (int) $subject['id'] : null,
                    'search_text' => $this->searchText($data),
                ]);
            });
    }

    /**
     * Columns this migration filled are cleared again; the rows stay.
     */
    public function down(): void
    {
        DB::table('notifications')->update([
            'module' => null,
            'kind' => 'action',
            'priority' => 'info',
            'subject_type' => null,
            'subject_id' => null,
            'search_text' => null,
        ]);
    }

    /**
     * HR's stored title and body in both languages, one per line.
     *
     * @param  array<string, mixed>  $data
     */
    private function searchText(array $data): ?string
    {
        $lines = [];

        foreach (['en', 'ar'] as $locale) {
            foreach (['title', 'body'] as $part) {
                $text = is_array($data[$part] ?? null) ? ($data[$part][$locale] ?? null) : null;

                if (is_string($text) && $text !== '') {
                    $lines[] = $text;
                }
            }
        }

        return $lines === [] ? null : implode("\n", $lines);
    }
};
