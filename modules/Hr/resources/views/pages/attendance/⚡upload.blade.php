<?php

use Flux\Flux;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Modules\Core\Models\User;
use Modules\Hr\Http\Controllers\Attendance\AttendanceUploadController;
use Modules\Hr\Models\AttendanceImport;
use Modules\Hr\Models\AttendancePunch;
use Modules\Hr\Models\Employee;
use Modules\Hr\Support\Attendance\AttendanceImporter;
use Modules\Hr\Support\Attendance\Roster;
use Modules\Hr\Support\Attendance\UnreadableReport;

/*
 * Upload the attendance report exported from the device program (the raw
 * .xls it saves, or a CSV/TXT). The file is posted to
 * AttendanceUploadController (Livewire uploads take only the hub's document
 * types), then read and shown here first: how many
 * punches, which period, which device numbers are not an employee yet;
 * nothing is stored until HR confirms. A number that is not an employee's ID
 * is tied to someone once, and stays tied.
 */
new #[Layout('layouts::desk'), Title('hr_attendance.titles.upload')] class extends Component {
    /** The report waiting in the uploader's own folder (AttendanceUploadController), by its token. */
    #[Locked]
    #[Url(as: 'report', except: '')]
    public string $report = '';

    /** @var array{total: int, invalid: int, from: ?string, to: ?string, unmatched: int, numbers: list<array{device_no: string, punches: int, name: ?string}>, name: string}|null */
    #[Locked]
    public ?array $preview = null;

    #[Locked]
    public ?int $importedId = null;

    /** @var array<string, string> Employee ID typed beside each unmatched device number. */
    public array $employeeIds = [];

    public function mount(): void
    {
        $this->authorizeManage();

        if ($this->report === '') {
            return;
        }

        $path = $this->reportPath();

        if ($path === null) {
            $this->report = '';

            return;
        }

        try {
            $summary = app(AttendanceImporter::class)->preview($path);
        } catch (UnreadableReport $unreadable) {
            $this->discardReport();
            $this->addError('report', __($unreadable->getMessage()));

            return;
        }

        $numbers = collect($summary['numbers']);

        $this->preview = [
            'total' => $summary['total'],
            'invalid' => $summary['invalid'],
            'from' => $summary['from'],
            'to' => $summary['to'],
            'unmatched' => $numbers->where('employee', null)->count(),
            'numbers' => $numbers->map(fn (array $row): array => [
                'device_no' => $row['device_no'],
                'punches' => $row['punches'],
                'name' => $row['employee']?->name,
            ])->all(),
            'name' => (string) session('attendance_upload_name', $this->report),
        ];
    }

    public function import(): void
    {
        $this->authorizeManage();

        $path = $this->reportPath();
        abort_if($path === null || $this->preview === null, 422);

        try {
            $import = app(AttendanceImporter::class)->import($path, $this->preview['name'], $this->user());
        } catch (UnreadableReport $unreadable) {
            $this->addError('report', __($unreadable->getMessage()));

            return;
        }

        $this->cancel();
        $this->importedId = $import->id;
        Flux::toast(variant: 'success', text: __('hr_attendance.upload.imported', ['new' => $import->punches_new]));
    }

    public function cancel(): void
    {
        $this->discardReport();
        $this->preview = null;
        $this->resetValidation();
    }

    /** Tie a device number to an employee, by the employee's ID, within the user's cities. */
    public function linkNumber(string $deviceNo): void
    {
        $this->authorizeManage();

        $id = trim($this->employeeIds[$deviceNo] ?? '');

        if ($id === '' || ! ctype_digit($id)) {
            $this->addError('employeeIds.'.$deviceNo, __('hr_attendance.upload.enter-id'));

            return;
        }

        $employee = Employee::query()->find((int) $id);

        if ($employee === null || ! app(Roster::class)->covers($this->user(), $employee)) {
            $this->addError('employeeIds.'.$deviceNo, __('hr_attendance.upload.no-such-employee'));

            return;
        }

        app(AttendanceImporter::class)->link($deviceNo, $employee, $this->user());

        unset($this->employeeIds[$deviceNo]);
        $this->resetValidation();
        Flux::toast(variant: 'success', text: __('hr_attendance.upload.linked', ['name' => $employee->name]));
    }

    /** @return array<string, mixed> */
    public function with(): array
    {
        return [
            'unlinked' => AttendancePunch::query()
                ->whereNull('employee_id')
                ->selectRaw('device_no, count(*) as punches, min(punched_at) as first_at, max(punched_at) as last_at')
                ->groupBy('device_no')
                ->orderBy('device_no')
                ->limit(200)
                ->get(),
            'imports' => AttendanceImport::query()->with('user')->latest('id')->limit(8)->get(),
        ];
    }

    private function reportPath(): ?string
    {
        return $this->report === '' ? null : AttendanceUploadController::path((int) $this->user()->id, $this->report);
    }

    private function discardReport(): void
    {
        if ($this->reportPath() !== null) {
            Storage::disk(AttendanceUploadController::DISK)->delete(AttendanceUploadController::folder((int) $this->user()->id).'/'.$this->report);
        }

        $this->report = '';
    }

    private function authorizeManage(): void
    {
        abort_unless($this->user()->can('attendance.manage'), 403);
    }

    private function user(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
};
?>

@php
    $muted = 'text-secondary-medium dark:text-ink-muted';
@endphp

<x-desk.register class="@container" heading-id="hr-attendance-upload-heading" :title="__('hr_attendance.upload.title')" data-hr-attendance-upload>
    <x-slot:actions>
        <x-ui.button tone="action" icon="signal" :href="route('hr.attendance.devices')" wire:navigate data-test="attendance-devices-link">{{ __('hr_attendance.devices.title') }}</x-ui.button>
        <x-ui.button tone="action" icon="clock" :href="route('hr.attendance.timesheets')" wire:navigate>{{ __('hr_attendance.upload.then') }}</x-ui.button>
    </x-slot:actions>

    <div class="grid gap-y-6">
        <x-ui.panel class="p-4">
            <p class="mb-3 text-sm {{ $muted }}">{{ __('hr_attendance.upload.lead') }}</p>
            <form method="POST" action="{{ route('hr.attendance.upload.store') }}" enctype="multipart/form-data" class="grid gap-y-2" data-attendance-upload-form>
                @csrf
                <label for="attendance-report" class="form-label">{{ __('hr_attendance.upload.choose') }} <span class="form-required" aria-hidden="true">*</span></label>
                <input id="attendance-report" type="file" name="report" accept=".xls,.csv,.txt" required aria-describedby="attendance-report-help"
                    class="block w-full max-w-xl rounded-md border border-panel-border bg-white px-3 py-2 text-sm dark:bg-surface-1" data-test="attendance-file">
                <p id="attendance-report-help" class="text-xs {{ $muted }}">{{ __('hr_attendance.upload.file-help') }}</p>
                @error('report')
                    <p class="text-sm font-semibold text-primary dark:text-brand-text" role="alert">{{ $message }}</p>
                @enderror
                <div>
                    <x-ui.button type="submit" size="sm" icon="arrow-up-tray">{{ __('hr_attendance.upload.read') }}</x-ui.button>
                </div>
            </form>
        </x-ui.panel>

        @if ($preview)
            <x-ui.panel class="p-4" data-attendance-preview>
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="grid gap-y-1">
                        <span class="font-semibold" dir="auto">{{ $preview['name'] }}</span>
                        <span class="text-sm {{ $muted }}">
                            {{ __('hr_attendance.upload.counts', ['punches' => number_format($preview['total']), 'numbers' => count($preview['numbers'])]) }}
                            @if ($preview['from'])
                                · {{ __('hr_attendance.upload.period') }}: <bdi class="tabular-nums">{{ $preview['from'] }} → {{ $preview['to'] }}</bdi>
                            @endif
                        </span>
                        @if ($preview['invalid'] > 0)
                            <span class="text-sm {{ $muted }}">{{ trans_choice('hr_attendance.upload.skipped', $preview['invalid'], ['count' => $preview['invalid']]) }}</span>
                        @endif
                        <span class="text-sm {{ $muted }}">
                            @if ($preview['unmatched'] > 0)
                                {{ trans_choice('hr_attendance.upload.summary-unmatched', $preview['unmatched'], ['count' => $preview['unmatched']]) }}
                            @else
                                {{ __('hr_attendance.upload.summary-matched') }}
                            @endif
                        </span>
                    </div>
                    <div class="flex gap-2">
                        <x-ui.button size="sm" wire:click="cancel">{{ __('hr_attendance.upload.cancel') }}</x-ui.button>
                        <x-ui.button tone="primary" size="sm" wire:click="import" wire:loading.attr="disabled" wire:target="import" data-test="attendance-import">{{ __('hr_attendance.upload.import', ['count' => number_format($preview['total'])]) }}</x-ui.button>
                    </div>
                </div>

                <x-ui.data-table class="mt-4" :caption="__('hr_attendance.upload.device-numbers')">
                    <x-slot:head>
                        <th>{{ __('hr_attendance.upload.device-no') }}</th>
                        <th>{{ __('hr_attendance.upload.employee') }}</th>
                        <th>{{ __('hr_attendance.upload.punches') }}</th>
                        <th>{{ __('hr_attendance.upload.status') }}</th>
                    </x-slot:head>
                    @foreach (array_slice($preview['numbers'], 0, 40) as $row)
                        <tr wire:key="preview-{{ $row['device_no'] }}">
                            <x-ui.data-table.cell :label="__('hr_attendance.upload.device-no')"><span class="font-semibold tabular-nums">{{ $row['device_no'] }}</span></x-ui.data-table.cell>
                            <x-ui.data-table.cell :label="__('hr_attendance.upload.employee')"><span dir="auto">{{ $row['name'] ?? '—' }}</span></x-ui.data-table.cell>
                            <x-ui.data-table.cell :label="__('hr_attendance.upload.punches')"><span class="tabular-nums">{{ $row['punches'] }}</span></x-ui.data-table.cell>
                            <x-ui.data-table.cell :label="__('hr_attendance.upload.status')">
                                @if ($row['name'])
                                    <x-ui.tone-badge tone="good" icon="check">{{ __('hr_attendance.upload.matched') }}</x-ui.tone-badge>
                                @else
                                    <x-ui.tone-badge tone="warn" icon="exclamation-triangle">{{ __('hr_attendance.upload.not-matched') }}</x-ui.tone-badge>
                                @endif
                            </x-ui.data-table.cell>
                        </tr>
                    @endforeach
                </x-ui.data-table>
                @if (count($preview['numbers']) > 40)
                    <p class="mt-2 text-xs {{ $muted }}">{{ __('hr_attendance.upload.more', ['count' => count($preview['numbers']) - 40]) }}</p>
                @endif
            </x-ui.panel>
        @endif

        @if ($importedId)
            <x-ui.panel class="p-4" data-attendance-imported>
                <p class="font-semibold">{{ __('hr_attendance.upload.imported', ['new' => $imports->firstWhere('id', $importedId)?->punches_new ?? 0]) }}</p>
                @php
                    $lockedCount = (int) ($imports->firstWhere('id', $importedId)?->punches_locked ?? 0);
                @endphp
                @if ($lockedCount > 0)
                    <p class="mt-1 text-sm {{ $muted }}">{{ trans_choice('hr_attendance.upload.locked-skipped', $lockedCount, ['count' => $lockedCount]) }}</p>
                @endif
            </x-ui.panel>
        @endif

        <x-ui.panel class="p-4" data-attendance-unlinked>
            <h2 class="font-semibold">{{ __('hr_attendance.upload.unlinked-title') }}</h2>
            <p class="mt-1 text-sm {{ $muted }}">{{ __('hr_attendance.upload.unlinked-lead') }}</p>

            @if ($unlinked->isEmpty())
                <p class="mt-4 text-sm {{ $muted }}">{{ __('hr_attendance.upload.none-unlinked') }}</p>
            @else
                <x-ui.data-table class="mt-4" :caption="__('hr_attendance.upload.unlinked-title')">
                    <x-slot:head>
                        <th>{{ __('hr_attendance.upload.device-no') }}</th>
                        <th>{{ __('hr_attendance.upload.punches') }}</th>
                        <th>{{ __('hr_attendance.upload.seen') }}</th>
                        <th>{{ __('hr_attendance.upload.employee-id') }}</th>
                    </x-slot:head>
                    @foreach ($unlinked as $row)
                        <tr wire:key="unlinked-{{ $row->device_no }}" data-unlinked="{{ $row->device_no }}">
                            <x-ui.data-table.cell :label="__('hr_attendance.upload.device-no')"><span class="font-semibold tabular-nums">{{ $row->device_no }}</span></x-ui.data-table.cell>
                            <x-ui.data-table.cell :label="__('hr_attendance.upload.punches')"><span class="tabular-nums">{{ $row->punches }}</span></x-ui.data-table.cell>
                            <x-ui.data-table.cell :label="__('hr_attendance.upload.seen')"><bdi class="text-xs tabular-nums">{{ substr((string) $row->first_at, 0, 10) }} → {{ substr((string) $row->last_at, 0, 10) }}</bdi></x-ui.data-table.cell>
                            <x-ui.data-table.cell :label="__('hr_attendance.upload.employee-id')">
                                <form wire:submit="linkNumber('{{ $row->device_no }}')" class="flex items-start gap-2">
                                    <div>
                                        <flux:input size="sm" inputmode="numeric" wire:model="employeeIds.{{ $row->device_no }}" :aria-label="__('hr_attendance.upload.employee-id')" class="w-32!" />
                                        @error('employeeIds.'.$row->device_no)
                                            <p class="mt-1 text-xs font-semibold text-primary dark:text-brand-text" role="alert">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <x-ui.button type="submit" size="sm">{{ __('hr_attendance.upload.link') }}</x-ui.button>
                                </form>
                            </x-ui.data-table.cell>
                        </tr>
                    @endforeach
                </x-ui.data-table>
            @endif
        </x-ui.panel>

        @if ($imports->isNotEmpty())
            <x-ui.panel class="p-4" data-attendance-imports>
                <h2 class="font-semibold">{{ __('hr_attendance.upload.recent') }}</h2>
                <x-ui.data-table class="mt-3" :caption="__('hr_attendance.upload.recent')">
                    <x-slot:head>
                        <th>{{ __('hr_attendance.upload.file') }}</th>
                        <th>{{ __('hr_attendance.upload.period') }}</th>
                        <th>{{ __('hr_attendance.upload.punches') }}</th>
                        <th>{{ __('hr_attendance.upload.new') }}</th>
                        <th>{{ __('hr_attendance.upload.by') }}</th>
                    </x-slot:head>
                    @foreach ($imports as $import)
                        <tr wire:key="import-{{ $import->id }}">
                            <x-ui.data-table.cell :label="__('hr_attendance.upload.file')"><span class="font-semibold" dir="auto">{{ $import->file_name }}</span></x-ui.data-table.cell>
                            <x-ui.data-table.cell :label="__('hr_attendance.upload.period')"><bdi class="tabular-nums">{{ $import->period_start?->toDateString() }} → {{ $import->period_end?->toDateString() }}</bdi></x-ui.data-table.cell>
                            <x-ui.data-table.cell :label="__('hr_attendance.upload.punches')"><span class="tabular-nums">{{ number_format($import->punches_total) }}</span></x-ui.data-table.cell>
                            <x-ui.data-table.cell :label="__('hr_attendance.upload.new')"><span class="tabular-nums">{{ number_format($import->punches_new) }}</span></x-ui.data-table.cell>
                            <x-ui.data-table.cell :label="__('hr_attendance.upload.by')"><span dir="auto">{{ $import->user?->name ?? '—' }}</span></x-ui.data-table.cell>
                        </tr>
                    @endforeach
                </x-ui.data-table>
            </x-ui.panel>
        @endif
    </div>
</x-desk.register>
