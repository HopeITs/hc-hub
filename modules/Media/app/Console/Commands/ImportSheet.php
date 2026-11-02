<?php

namespace Modules\Media\Console\Commands;

use App\Models\City;
use App\Models\Program;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Hr\Models\Employee;
use Modules\Media\Enums\MediaRequestStatus as S;
use Modules\Media\Models\MediaRequest;
use Modules\Media\Support\MediaNaming;
use Throwable;

/**
 * Brings the old Google sheet ("MEDIA Request (Responses)", exported as CSV) into the app,
 * so its history can be read: when, who asked, for which program and place, what, who did
 * it, and how it ended. Nothing else from the sheet is kept.
 *
 *   php artisan media:import-sheet "MEDIA Request (Responses).csv" --dry-run
 *   php artisan media:import-sheet "MEDIA Request (Responses).csv"
 *
 * Names written in several ways are joined with a map kept on the server only (staff names,
 * so never in git): storage/app/private/sheet-map.json. Every person is an HR employee: names
 * are matched to the hub's employees (English first and last name, or a team member's email
 * in the map) and nobody is created. A row whose requester is not found is left out and
 * listed, so the map can be completed and the command run again.
 *
 *   Done, or approved with no status    delivered (the sheet has no delivery date: the needed-by
 *                                       day stands in, and timing statistics leave these out)
 *   Rejected / cancelled                rejected / cancelled
 *   Still open, asked in the last 45    open again, at the matching stage
 *   days
 *   Still open and older                left out
 *   Publishing (Upload, Publish)        left out, written to storage/app/private/sheet-publishing.csv
 *                                       for the publishing section that comes later
 *
 * Running it again skips rows already brought in (imported_from = "sheet:<row>").
 */
class ImportSheet extends Command
{
    /** Sheet column, by its position (the headers have stray spaces). */
    private const COL = ['time' => 0, 'name' => 1, 'section' => 4, 'city' => 5, 'for' => 6, 'title' => 7, 'type' => 8,
        'deadline' => 11, 'priority' => 12, 'pmApprv' => 13, 'ctApprv' => 14, 'notes' => 15, 'officer' => 19, 'status' => 20, 'done' => 24];

    protected $signature = 'media:import-sheet {file : The sheet, as CSV} {--map= : Name map (default storage/app/private/sheet-map.json)}
        {--open-days=45 : Open requests newer than this are brought in as open} {--dry-run : Only show what would happen}';

    protected $description = 'Bring the old Google sheet of media requests into the app';

    /**
     * The name map (storage/app/private/sheet-map.json).
     *
     * @var array{drop?: array<int, string>, people?: array<string, string>, officers?: array<string, string>, programs?: array<string, string>}
     */
    private array $map = [];

    /** @var array<string, Employee|null> */
    private array $people = [];

    public function handle(MediaNaming $naming): int
    {
        $file = (string) $this->argument('file');
        $mapFile = (string) ($this->option('map') ?: storage_path('app/private/sheet-map.json'));
        if (! is_readable($file) || ($handle = fopen($file, 'r')) === false) {
            $this->error("Cannot read {$file}");

            return self::FAILURE;
        }
        $this->map = is_readable($mapFile) ? (json_decode((string) file_get_contents($mapFile), true) ?: []) : [];

        fgetcsv($handle, escape: '\\');   // headers
        $rows = [];
        for ($n = 2; ($line = fgetcsv($handle, escape: '\\')) !== false; $n++) {
            if (trim((string) ($line[self::COL['time']] ?? '')) !== '') {
                $rows[$n] = $line;
            }
        }
        fclose($handle);

        $since = now()->subDays((int) $this->option('open-days'))->startOfDay();
        $count = ['delivered' => 0, 'rejected' => 0, 'cancelled' => 0, 'open' => 0, 'oldOpen' => 0, 'publishing' => 0, 'dropped' => 0, 'noPerson' => 0, 'already' => 0];
        $plans = [];
        $publishing = [];
        $unknown = [];

        foreach ($rows as $n => $row) {
            $v = fn (string $k) => trim((string) ($row[self::COL[$k]] ?? ''));
            $at = $this->date($v('time'));
            $who = $this->person($v('name'), $v('city'));

            if ($who === null || $at === null) {
                $count['dropped']++;

                continue;
            }
            $requester = $this->employee($who['en'], $this->citySlug($v('city')));
            if ($requester === null) {
                $count['noPerson']++;
                $unknown['person: '.$who['en']] = true;

                continue;
            }
            if (MediaRequest::where('imported_from', 'sheet:'.$n)->exists()) {
                $count['already']++;

                continue;
            }
            [$service, $type] = $this->kind($v('section'), $v('type'));
            if ($service === null) {
                $count['publishing']++;
                $publishing[] = $row;

                continue;
            }
            $status = $this->status($v('status'), $v('ctApprv'), $v('pmApprv'), $v('done'), $v('officer'));
            if (in_array($status, S::open(), true) && $at->lt($since)) {
                $count['oldOpen']++;

                continue;
            }
            $count[match ($status) {
                S::Delivered => 'delivered', S::Rejected => 'rejected', S::Cancelled => 'cancelled', default => 'open',
            }]++;

            $program = $this->program($v('for'));
            $officers = $this->officers($v('officer'));
            if ($program === null) {
                $unknown['program: '.$v('for')] = true;
            }
            $plans[$n] = compact('at', 'who', 'requester', 'service', 'type', 'status', 'program', 'officers') + ['v' => $v];
        }

        $this->table(['Delivered', 'Rejected', 'Cancelled', 'Open (recent)', 'Open, older: left out', 'Publishing: kept aside', 'Unreadable: left out', 'Not an HR employee: left out', 'Already in'],
            [array_values($count)]);
        $names = collect($plans)->map(fn ($p) => $p['who']['en'])->countBy()->sortKeys();
        $this->line('People who asked ('.$names->count().'): '.$names->map(fn ($c, $name) => "{$name} ({$c})")->implode(', '));
        foreach (array_keys($unknown) as $u) {
            $this->warn("Not found, left empty: {$u}");
        }

        if ($this->option('dry-run')) {
            $this->info('Dry run: nothing was saved.');

            return self::SUCCESS;
        }

        $cities = City::pluck('id', 'slug');
        $mm = User::where('email', 'fares.badawi@hcsyria.org')->first()->employee_id
            ?? app(\Modules\Media\Support\MediaTeam::class)->managers()->first()?->id;

        DB::transaction(function () use ($plans, $naming, $cities, $mm): void {
            foreach (collect($plans)->sortBy(fn ($p) => $p['at']->timestamp) as $n => $p) {
                $v = $p['v'];
                $requester = $p['requester'];
                $assignee = $p['officers'][0] ?? null;
                $due = $this->date($v('deadline')) ?? $p['at']->copy();
                $closed = in_array($p['status'], [S::Delivered, S::Rejected, S::Cancelled], true);
                $note = array_filter([
                    'Imported from the Google sheet (row '.$n.').',
                    count($p['officers']) > 1 ? 'Team: '.collect($p['officers'])->map->nameForLocale('en')->implode(', ').'.' : null,
                    $v('type') !== '' && ! in_array($v('type'), ['New', 'Edit', 'Export', 'Print'], true) ? 'Type in the sheet: '.$v('type') : null,
                    $v('notes') !== '' ? 'Manager notes: '.$v('notes') : null,
                    in_array($v('ctApprv'), ['Missing Details', 'On Hold', 'Need Edit'], true) ? 'Sheet status: '.$v('ctApprv') : null,
                ]);

                $r = new MediaRequest([
                    'reference' => $naming->nextReference($p['service'], $p['at']),
                    'requester_id' => $requester->id,
                    'program_id' => $p['program']?->id,
                    'city_id' => $cities[$this->citySlug($v('city'))] ?? null,
                    'service' => $p['service'],
                    'type' => $p['type'],
                    'item_code' => $naming->itemCode($p['service'], $p['type'], []),
                    'title' => Str::limit($v('title') ?: 'Request', 117),
                    'description' => implode("\n", $note),
                    'answers' => $v('type') === 'New, Print' ? ['alsoPrint' => 'yes'] : [],
                    'needed_on' => $due->toDateString(),
                    'status' => $p['status'],
                    'status_changed_at' => $closed ? $due : $p['at'],
                    'is_program_approval_needed' => $p['status'] === S::ProgramApproval,
                    'media_approver_id' => in_array($p['status'], [S::MediaApproval, S::ProgramApproval], true) ? null : $mm,
                    'media_approved_at' => in_array($p['status'], [S::MediaApproval, S::ProgramApproval], true) ? null : $p['at'],
                    'assignee_id' => in_array($p['status'], [S::ToClaim, S::MediaApproval, S::ProgramApproval], true) ? null : $assignee?->id,
                    'priority' => match ($v('priority')) {
                        '5', '4' => 'high', '1', '2' => 'low', default => 'normal',
                    },
                    'delivered_at' => $p['status'] === S::Delivered ? $due : null,
                    'imported_from' => 'sheet:'.$n,
                ]);
                $r->created_at = $p['at'];
                $r->updated_at = $p['at'];
                $r->save();
            }
        });

        if ($publishing !== [] && ($out = fopen(storage_path('app/private/sheet-publishing.csv'), 'w')) !== false) {
            foreach ($publishing as $row) {
                fputcsv($out, $row, escape: '\\');
            }
            fclose($out);
        }
        $this->info(count($plans).' requests brought in.'.($publishing ? ' Publishing rows kept in storage/app/private/sheet-publishing.csv.' : ''));

        return self::SUCCESS;
    }

    /** @return array{en: string, ar: string}|null */
    private function person(string $raw, string $city): ?array
    {
        $key = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $raw) ?? ''));
        if ($key === '' || in_array($key, $this->map['drop'] ?? [], true)) {
            return null;
        }
        $to = $this->map['people'][$key.'@'.$this->citySlug($city)] ?? $this->map['people'][$key] ?? null;
        [$en, $ar] = array_pad(explode('|', (string) ($to ?? '')), 2, '');
        $en = $en !== '' ? $en : (preg_match('/\p{Arabic}/u', $raw) ? trim($raw) : Str::title(trim($raw)));

        return ['en' => $en, 'ar' => $ar !== '' ? $ar : $en];
    }

    /** @return array<int, Employee> */
    private function officers(string $raw): array
    {
        $out = [];
        foreach (preg_split('/\s*,\s*/', $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $short) {
            $to = $this->map['officers'][mb_strtolower(trim($short))] ?? null;
            if ($to === null) {
                continue;
            }
            if (str_contains($to, '@hcsyria.org') || filter_var($to, FILTER_VALIDATE_EMAIL)) {
                $out[] = \Modules\Media\Support\MediaTeam::employeeOf(User::where('email', $to)->first())
                    ?? $this->employee(Str::title(str_replace(['.', '_'], ' ', Str::before($to, '@'))), null);
            } else {
                [$name, $city] = array_pad(explode('@', $to), 2, null);
                $out[] = $this->employee($name, $city);
            }
        }

        return collect($out)->filter()->unique('id')->values()->all();
    }

    /**
     * The HR employee by English name ("George Alhorani": first name George, last name
     * Alhorani, any case), the one in $city first when several share it; null when the hub
     * has nobody of that name.
     */
    private function employee(string $en, ?string $city): ?Employee
    {
        $key = mb_strtolower(trim($en));
        if (array_key_exists($key, $this->people)) {
            return $this->people[$key];
        }
        $first = Str::before(trim($en), ' ');
        $last = Str::contains(trim($en), ' ') ? trim(Str::after(trim($en), ' ')) : '';
        $cityId = $city ? City::where('slug', strtoupper($city))->value('id') : null;

        return $this->people[$key] = Employee::query()
            ->whereRaw('lower(first_name_en) = ?', [mb_strtolower($first)])
            ->whereRaw("lower(coalesce(last_name_en, '')) = ?", [mb_strtolower($last)])
            ->orderByRaw('case when city_id = ? then 0 else 1 end', [$cityId])
            ->orderBy('id')
            ->first();
    }

    /**
     * [service, type]; [null, null] for publishing, kept aside.
     *
     * @return array{0: string|null, 1: string|null}
     */
    private function kind(string $section, string $type): array
    {
        $t = mb_strtolower($type);
        if (preg_match('/upload|publish|نشر|facebook/u', $t)) {
            return [null, null];
        }
        $photoWords = preg_match('/تصوير|نصوير|photo|coverage/u', $t) === 1;
        $sendPhotos = str_contains($t, 'export') || str_contains($t, 'ارسال');

        return match (true) {
            $section === 'Photo' || ($section === 'Other' && $photoWords) => ['002', $sendPhotos ? 'export' : 'shoot'],
            $section === 'Video' => ['003', str_contains($t, 'edit') || str_contains($t, 'export') ? 'export' : 'shoot'],
            str_starts_with($t, 'print') => ['001', 'print'],
            str_starts_with($t, 'edit') || str_starts_with($t, 'export') => ['001', 'edit'],
            default => ['001', 'new'],
        };
    }

    private function status(string $status, string $ct, string $pm, string $done, string $officer): string
    {
        return match (true) {
            $ct === 'Rejected' => S::Rejected,
            mb_strtolower($status) === 'cancelled' => S::Cancelled,
            $status === 'Done' || strtoupper($done) === 'TRUE' => S::Delivered,
            $status === '' && $ct === 'Approved' => S::Delivered,
            $ct === 'Waiting Prgm M APRV' => S::ProgramApproval,
            $status === 'In Progress' || $ct === 'Need Edit' => $officer !== '' ? S::InProgress : S::ToClaim,
            $ct === 'Approved' => $officer !== '' ? S::Assigned : S::ToClaim,
            default => S::MediaApproval,   // pending, missing details, on hold: back with the media manager
        };
    }

    private function program(string $raw): ?Program
    {
        $slug = $this->map['programs'][mb_strtolower(trim($raw))] ?? null;

        return $slug ? Program::where('slug', $slug)->first() : null;
    }

    /** "002 HMS" → HMS */
    private function citySlug(string $raw): string
    {
        return strtoupper(trim((string) Str::afterLast(trim($raw), ' ')));
    }

    /** The sheet's dates: "1/16/2026 9:21:16" or "1/19/2026" (month first), Damascus time. */
    private function date(string $raw): ?Carbon
    {
        foreach (['n/j/Y G:i:s', 'n/j/Y'] as $format) {
            try {
                $d = Carbon::createFromFormat($format, trim($raw), 'Asia/Damascus');
                if ($d !== null) {
                    return $format === 'n/j/Y' ? $d->startOfDay() : $d;
                }
            } catch (Throwable) {
            }
        }

        return null;
    }
}
