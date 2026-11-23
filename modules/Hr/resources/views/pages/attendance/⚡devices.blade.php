<?php

use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Modules\Core\Models\Center;
use Modules\Core\Models\User;
use Modules\Hr\Models\AttendanceDevice;
use Modules\Hr\Models\AttendanceDeviceSighting;
use Modules\Hr\Support\Employees\HrAccess;

/*
 * The time-clock devices and the centers they hang in. A device is added once, by its serial number,
 * and from then on pushes its own punches (ZKTeco "Cloud Server", ADMS): no report to download, no
 * visit to the center. The page shows which devices are talking, which have gone quiet and which
 * centers have none, so HR knows where to look before a timesheet is found empty. A city HR sees
 * its own centers (attendance.manage, checked by the route and again on every action).
 */
new #[Layout('layouts::desk'), Title('hr_attendance.devices.title')] class extends Component {
    /** @var array<string, string> Name typed beside each device that called but is not registered yet. */
    public array $newName = [];

    /** @var array<string, string> Center chosen beside each device that called but is not registered yet. */
    public array $newCenter = [];

    #[Locked]
    public ?int $editingId = null;

    public string $editName = '';

    public string $editCenter = '';

    /** A sync key just made, shown once for that device and then gone (only its hash is kept). */
    #[Locked]
    public ?string $newKey = null;

    #[Locked]
    public ?int $keyForId = null;

    /** Add a device by its serial (Menu, System Info on the device), for one read by the LAN agent that never calls. */
    public string $addSerial = '';

    public string $addCenter = '';

    public string $addName = '';

    public function mount(): void
    {
        $this->authorizeManage();
    }

    public function addBySerial(): void
    {
        $this->authorizeManage();

        $serial = strtoupper(trim($this->addSerial));

        if (! preg_match('/^[A-Z0-9]{5,32}$/', $serial)) {
            $this->addError('addSerial', __('hr_attendance.devices.serial-invalid'));

            return;
        }

        $centerId = $this->validCenter($this->addCenter);

        if ($centerId === null) {
            $this->addError('addCenter', __('hr_attendance.devices.choose-center'));

            return;
        }

        $name = trim($this->addName);

        AttendanceDevice::query()->firstOrCreate(['serial' => $serial], [
            'name' => $name !== '' ? mb_substr($name, 0, 120) : Center::query()->find($centerId)?->name,
            'center_id' => $centerId,
            'created_by' => $this->user()->id,
        ]);
        AttendanceDeviceSighting::query()->where('serial', $serial)->delete();

        $this->reset('addSerial', 'addCenter', 'addName');
        $this->resetErrorBag();
        Flux::toast(variant: 'success', text: __('hr_attendance.devices.added'));
    }

    /** Register a device that called, with the center it hangs in. */
    public function register(string $serial): void
    {
        $this->authorizeManage();

        $sighting = AttendanceDeviceSighting::query()->where('serial', $serial)->firstOrFail();
        $centerId = $this->validCenter($this->newCenter[$serial] ?? '');

        if ($centerId === null) {
            $this->addError('newCenter.'.$serial, __('hr_attendance.devices.choose-center'));

            return;
        }

        $name = trim($this->newName[$serial] ?? '');

        AttendanceDevice::query()->firstOrCreate(['serial' => $sighting->serial], [
            'name' => $name !== '' ? mb_substr($name, 0, 120) : Center::query()->find($centerId)?->name,
            'center_id' => $centerId,
            'created_by' => $this->user()->id,
        ]);
        $sighting->delete();

        unset($this->newName[$serial], $this->newCenter[$serial]);
        $this->resetErrorBag();
        Flux::toast(variant: 'success', text: __('hr_attendance.devices.added'));
    }

    public function forgetSighting(string $serial): void
    {
        $this->authorizeManage();

        AttendanceDeviceSighting::query()->where('serial', $serial)->delete();
    }

    public function edit(int $id): void
    {
        $this->authorizeManage();

        $device = $this->deviceInScope($id);

        $this->editingId = $device->id;
        $this->editName = (string) $device->name;
        $this->editCenter = (string) ($device->center_id ?? '');
        $this->resetErrorBag();
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
        $this->resetErrorBag();
    }

    public function saveDevice(): void
    {
        $this->authorizeManage();

        $device = $this->deviceInScope((int) $this->editingId);
        $centerId = $this->validCenter($this->editCenter);

        if ($centerId === null) {
            $this->addError('editCenter', __('hr_attendance.devices.choose-center'));

            return;
        }

        $device->update(['name' => mb_substr(trim($this->editName), 0, 120) ?: null, 'center_id' => $centerId]);

        $this->cancelEdit();
        Flux::toast(variant: 'success', text: __('hr_attendance.devices.saved'));
    }

    /** A blocked device is refused until it is let in again; what it already sent stays. */
    public function toggleBlock(int $id): void
    {
        $this->authorizeManage();

        $device = $this->deviceInScope($id);
        $device->update(['status' => $device->isBlocked() ? AttendanceDevice::ACTIVE : AttendanceDevice::BLOCKED]);
    }

    /** Make (or replace) the key the agent on a computer next to this device sends its punches with. */
    public function createKey(int $id): void
    {
        $this->authorizeManage();

        $device = $this->deviceInScope($id);
        $this->newKey = $device->issueAgentKey();
        $this->keyForId = $device->id;
    }

    public function revokeKey(int $id): void
    {
        $this->authorizeManage();

        $this->deviceInScope($id)->revokeAgentKey();

        if ($this->keyForId === $id) {
            $this->hideKey();
        }

        Flux::toast(variant: 'success', text: __('hr_attendance.devices.key-revoked'));
    }

    public function hideKey(): void
    {
        $this->newKey = null;
        $this->keyForId = null;
    }

    /** Take a device off the list. Its punches stay; it can be added again. */
    public function removeDevice(int $id): void
    {
        $this->authorizeManage();

        $this->deviceInScope($id)->delete();
        Flux::toast(variant: 'success', text: __('hr_attendance.devices.removed'));
    }

    /** @return array<string, mixed> */
    public function with(): array
    {
        $centers = $this->centers();
        $health = ['silent' => 0, 'never' => 1, 'late' => 2, 'online' => 3];

        $devices = AttendanceDevice::query()
            ->with('center.city')
            ->where(fn ($query) => $query->whereNull('center_id')->orWhereIn('center_id', $centers->modelKeys()))
            ->get()
            ->sortBy(fn (AttendanceDevice $device): array => [$health[$device->health()] + ($device->isBlocked() ? 4 : 0), mb_strtolower((string) $device->name)])
            ->values();

        $registered = $devices->pluck('center_id')->filter()->unique();

        return [
            'devices' => $devices,
            'sightings' => AttendanceDeviceSighting::query()->orderByDesc('last_seen_at')->limit(20)->get(),
            'centers' => $centers,
            // Centers with no device yet: where to set one up.
            'withoutDevice' => $centers->reject(fn (Center $center): bool => $registered->contains($center->id))->values(),
            'host' => (string) parse_url((string) config('app.url'), PHP_URL_HOST),
        ];
    }

    /** @return Collection<int, Center> the centers this user may attach a device to (a city HR: its own cities') */
    private function centers(): Collection
    {
        $cities = HrAccess::cityIds($this->user());

        return Center::query()
            ->with('city')
            ->where('is_active', true)
            ->when($cities !== null, fn ($query) => $query->whereIn('city_id', $cities ?: [0]))
            ->orderBy(Center::nameColumn())
            ->get();
    }

    private function validCenter(string $value): ?int
    {
        return ctype_digit($value) && $this->centers()->contains('id', (int) $value) ? (int) $value : null;
    }

    private function deviceInScope(int $id): AttendanceDevice
    {
        $device = AttendanceDevice::query()->findOrFail($id);

        // A device already tied to a center of another city is out of reach of a city HR.
        abort_unless($device->center_id === null || $this->centers()->contains('id', $device->center_id), 403);

        return $device;
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
    $tone = ['online' => 'green', 'late' => 'amber', 'silent' => 'red', 'never' => 'zinc'];
    $icon = ['online' => 'check', 'late' => 'clock', 'silent' => 'exclamation-triangle', 'never' => 'minus'];
@endphp

<x-desk.register class="@container" heading-id="hr-attendance-devices-heading" :title="__('hr_attendance.devices.title')" :count="$devices->count()" data-hr-attendance-devices>
    <x-slot:actions>
        <x-ui.button tone="action" icon="clock" :href="route('hr.attendance.timesheets')" wire:navigate>{{ __('hr_attendance.upload.then') }}</x-ui.button>
        <x-ui.button tone="action" icon="arrow-up-tray" :href="route('hr.attendance.upload')" wire:navigate>{{ __('hr_attendance.devices.file-fallback') }}</x-ui.button>
    </x-slot:actions>

    <div class="grid gap-y-6">
        <p class="text-sm {{ $muted }}">{{ __('hr_attendance.devices.lead') }}</p>

        {{-- A device read by the LAN agent never calls, so it is added by its serial. --}}
        <x-ui.panel class="grid gap-y-3 p-4" data-device-add>
            <div>
                <h2 class="font-semibold">{{ __('hr_attendance.devices.add-title') }}</h2>
                <p class="text-sm {{ $muted }}">{{ __('hr_attendance.devices.add-help') }}</p>
            </div>
            <form wire:submit="addBySerial" class="flex flex-wrap items-start gap-3">
                <x-ui.form-input wire:model="addSerial" name="addSerial" :label="__('hr_attendance.devices.serial')" dir="ltr" class="w-52" />
                <x-ui.form-select wire:model="addCenter" name="addCenter" :label="__('hr_attendance.devices.center')" :placeholder="__('hr_attendance.devices.choose-center')" class="w-64">
                    @foreach ($centers as $center)
                        <option value="{{ $center->id }}">{{ $center->name }} · {{ $center->city?->name }}</option>
                    @endforeach
                </x-ui.form-select>
                <x-ui.form-input wire:model="addName" name="addName" :label="__('hr_attendance.devices.name')" class="w-48" />
                <x-ui.button type="submit" size="sm" class="self-end" data-test="device-add">{{ __('hr_attendance.devices.add') }}</x-ui.button>
            </form>
        </x-ui.panel>

        {{-- A device that called but is not registered: one click to add it. --}}
        @if ($sightings->isNotEmpty())
            <x-ui.panel class="grid gap-y-3 p-4" data-device-sightings>
                <div>
                    <h2 class="font-semibold">{{ __('hr_attendance.devices.sightings-title') }}</h2>
                    <p class="text-sm {{ $muted }}">{{ __('hr_attendance.devices.sightings-help') }}</p>
                </div>
                @foreach ($sightings as $sighting)
                    <form wire:submit="register({{ \Illuminate\Support\Js::from($sighting->serial) }})" wire:key="sighting-{{ $sighting->serial }}" class="flex flex-wrap items-start gap-3 border-t border-panel-border pt-3" data-sighting="{{ $sighting->serial }}">
                        <div class="min-w-48">
                            <p class="font-semibold tabular-nums" dir="ltr">{{ $sighting->serial }}</p>
                            <p class="text-sm {{ $muted }}">
                                {{ __('hr_attendance.devices.last-call') }}: <bdi class="tabular-nums">{{ $sighting->last_seen_at?->diffForHumans() }}</bdi>
                                @if ($sighting->last_ip)
                                    · <span dir="ltr">{{ $sighting->last_ip }}</span>
                                @endif
                            </p>
                        </div>
                        <x-ui.form-select wire:model="newCenter.{{ $sighting->serial }}" :label="__('hr_attendance.devices.center')" :placeholder="__('hr_attendance.devices.choose-center')" class="w-64">
                            @foreach ($centers as $center)
                                <option value="{{ $center->id }}">{{ $center->name }} · {{ $center->city?->name }}</option>
                            @endforeach
                        </x-ui.form-select>
                        <x-ui.form-input wire:model="newName.{{ $sighting->serial }}" :label="__('hr_attendance.devices.name')" class="w-48" />
                        <div class="flex items-center gap-3 self-end">
                            <x-ui.button type="submit" size="sm" data-test="device-register">{{ __('hr_attendance.devices.add') }}</x-ui.button>
                            <button type="button" wire:click="forgetSighting({{ \Illuminate\Support\Js::from($sighting->serial) }})" class="text-sm underline {{ $muted }} hover:text-primary">{{ __('hr_attendance.devices.ignore') }}</button>
                        </div>
                    </form>
                @endforeach
            </x-ui.panel>
        @endif

        {{-- The registered devices, the quiet ones first. --}}
        <x-ui.panel class="p-4" data-device-list>
            <h2 class="font-semibold">{{ __('hr_attendance.devices.list-title') }}</h2>

            @if ($devices->isEmpty())
                <p class="mt-3 text-sm {{ $muted }}">{{ __('hr_attendance.devices.none') }}</p>
            @else
                <x-ui.data-table class="mt-3" :caption="__('hr_attendance.devices.list-title')">
                    <x-slot:head>
                        <th>{{ __('hr_attendance.devices.device') }}</th>
                        <th>{{ __('hr_attendance.devices.center') }}</th>
                        <th>{{ __('hr_attendance.devices.state') }}</th>
                        <th>{{ __('hr_attendance.devices.key-title') }}</th>
                        <th>{{ __('hr_attendance.devices.last-seen') }}</th>
                        <th>{{ __('hr_attendance.devices.last-punch') }}</th>
                        <th><span class="sr-only">{{ __('hr_attendance.devices.actions') }}</span></th>
                    </x-slot:head>
                    @foreach ($devices as $device)
                        @php
                            $state = $device->health();
                        @endphp
                        <tr wire:key="device-{{ $device->id }}" data-health="{{ $state }}" data-device="{{ $device->serial }}">
                            @if ($editingId === $device->id)
                                <td colspan="7">
                                    <form wire:submit="saveDevice" class="flex flex-wrap items-start gap-3">
                                        <x-ui.form-input wire:model="editName" :label="__('hr_attendance.devices.name')" class="w-56" />
                                        <x-ui.form-select wire:model="editCenter" :label="__('hr_attendance.devices.center')" :placeholder="__('hr_attendance.devices.choose-center')" class="w-64">
                                            @foreach ($centers as $center)
                                                <option value="{{ $center->id }}">{{ $center->name }} · {{ $center->city?->name }}</option>
                                            @endforeach
                                        </x-ui.form-select>
                                        <div class="flex items-center gap-2 self-end">
                                            <x-ui.button type="submit" size="sm" data-test="device-save">{{ __('hr_attendance.devices.save') }}</x-ui.button>
                                            <x-ui.button size="sm" wire:click="cancelEdit">{{ __('hr_attendance.devices.cancel') }}</x-ui.button>
                                        </div>
                                    </form>
                                </td>
                            @else
                                <x-ui.data-table.cell :label="__('hr_attendance.devices.device')">
                                    <span class="block font-semibold" dir="auto">{{ $device->name ?: '—' }}</span>
                                    <span class="block text-xs {{ $muted }} tabular-nums" dir="ltr">{{ $device->serial }}</span>
                                </x-ui.data-table.cell>
                                <x-ui.data-table.cell :label="__('hr_attendance.devices.center')">
                                    <span dir="auto">{{ $device->center?->name ?? '—' }}</span>
                                    @if ($device->center?->city)
                                        <span class="block text-xs {{ $muted }}">{{ $device->center->city->name }}</span>
                                    @endif
                                </x-ui.data-table.cell>
                                <x-ui.data-table.cell :label="__('hr_attendance.devices.state')">
                                    @if ($device->isBlocked())
                                        <flux:badge size="sm" color="red" icon="no-symbol">{{ __('hr_attendance.devices.blocked') }}</flux:badge>
                                    @else
                                        <flux:badge size="sm" :color="$tone[$state]" :icon="$icon[$state]">{{ __('hr_attendance.devices.health.'.$state) }}</flux:badge>
                                    @endif
                                </x-ui.data-table.cell>
                                <x-ui.data-table.cell :label="__('hr_attendance.devices.key-title')">
                                    @if ($device->hasAgentKey())
                                        <span class="block font-semibold tabular-nums" dir="ltr">{{ __('hr_attendance.devices.key-masked', ['hint' => $device->agent_key_hint]) }}</span>
                                        <span class="block text-xs {{ $muted }}">
                                            @if ($device->agent_last_sync_at)
                                                {{ __('hr_attendance.devices.agent-last') }}: <bdi class="tabular-nums">{{ $device->agent_last_sync_at->diffForHumans() }}</bdi>
                                            @else
                                                {{ __('hr_attendance.devices.agent-never') }}
                                            @endif
                                        </span>
                                        <button type="button" wire:click="createKey({{ $device->id }})" wire:confirm="{{ __('hr_attendance.devices.key-regenerate-confirm') }}" class="text-sm underline hover:text-primary" data-test="device-key-regenerate">{{ __('hr_attendance.devices.key-regenerate') }}</button>
                                        <button type="button" wire:click="revokeKey({{ $device->id }})" wire:confirm="{{ __('hr_attendance.devices.key-revoke-confirm') }}" class="ms-2 text-sm underline {{ $muted }} hover:text-primary" data-test="device-key-revoke">{{ __('hr_attendance.devices.key-revoke') }}</button>
                                    @else
                                        <span class="block {{ $muted }}">{{ __('hr_attendance.devices.key-none') }}</span>
                                        <button type="button" wire:click="createKey({{ $device->id }})" class="text-sm underline hover:text-primary" data-test="device-key-create">{{ __('hr_attendance.devices.key-create') }}</button>
                                    @endif
                                </x-ui.data-table.cell>
                                <x-ui.data-table.cell :label="__('hr_attendance.devices.last-seen')"><span class="text-sm tabular-nums">{{ $device->last_seen_at?->diffForHumans() ?? '—' }}</span></x-ui.data-table.cell>
                                <x-ui.data-table.cell :label="__('hr_attendance.devices.last-punch')"><bdi class="text-sm tabular-nums">{{ $device->last_punch_at?->format('d/m/Y H:i') ?? '—' }}</bdi></x-ui.data-table.cell>
                                <x-ui.data-table.cell>
                                    <div class="flex flex-wrap justify-end gap-1">
                                        <x-ui.button tone="ghost" size="sm" icon="pencil-square" wire:click="edit({{ $device->id }})" :aria-label="__('hr_attendance.devices.edit')" data-test="device-edit" />
                                        <x-ui.button tone="ghost" size="sm" :icon="$device->isBlocked() ? 'lock-open' : 'no-symbol'" wire:click="toggleBlock({{ $device->id }})" :aria-label="$device->isBlocked() ? __('hr_attendance.devices.unblock') : __('hr_attendance.devices.block')" data-test="device-block" />
                                        <x-ui.button tone="ghost" size="sm" icon="trash" wire:click="removeDevice({{ $device->id }})" wire:confirm="{{ __('hr_attendance.devices.remove-confirm') }}" :aria-label="__('hr_attendance.devices.remove')" data-test="device-remove" />
                                    </div>
                                </x-ui.data-table.cell>
                            @endif
                        </tr>
                        {{-- A key just made: shown once, here, and never again. --}}
                        @if ($newKey && $keyForId === $device->id)
                            <tr wire:key="device-key-{{ $device->id }}" data-new-key>
                                <td colspan="7">
                                    <p class="font-semibold">{{ __('hr_attendance.devices.key-shown-once') }}</p>
                                    <div class="mt-2 flex flex-wrap items-center gap-2">
                                        <code class="select-all rounded bg-zinc-100 px-3 py-2 font-semibold dark:bg-white/10" dir="ltr">{{ $newKey }}</code>
                                        <x-ui.button size="sm" type="button" x-data="{}" x-on:click="navigator.clipboard.writeText($el.dataset.key)" data-key="{{ $newKey }}">{{ __('hr_attendance.devices.key-copy') }}</x-ui.button>
                                        <x-ui.button size="sm" tone="ghost" wire:click="hideKey">{{ __('hr_attendance.devices.key-hide') }}</x-ui.button>
                                    </div>
                                    <p class="mt-2 text-sm {{ $muted }}">{{ __('hr_attendance.devices.key-use') }}</p>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </x-ui.data-table>
            @endif
        </x-ui.panel>

        {{-- Centers with no device yet --}}
        @if ($withoutDevice->isNotEmpty())
            <x-ui.panel class="p-4" data-centers-without-device>
                <h2 class="font-semibold">{{ __('hr_attendance.devices.without-title') }}</h2>
                <p class="mt-1 text-sm {{ $muted }}">{{ __('hr_attendance.devices.without-help') }}</p>
                <p class="mt-3 flex flex-wrap gap-2">
                    @foreach ($withoutDevice as $center)
                        <flux:badge size="sm" color="zinc"><bdi>{{ $center->name }} · {{ $center->city?->name }}</bdi></flux:badge>
                    @endforeach
                </p>
            </x-ui.panel>
        @endif

        {{-- What to type on the device --}}
        <x-ui.panel class="grid gap-y-3 p-4" data-device-setup>
            <h2 class="font-semibold">{{ __('hr_attendance.devices.setup-title') }}</h2>
            <p class="text-sm {{ $muted }}">{{ __('hr_attendance.devices.setup-intro') }}</p>
            <dl class="grid max-w-xl gap-x-6 gap-y-2 sm:grid-cols-2">
                <div><dt class="text-sm {{ $muted }}">{{ __('hr_attendance.devices.setup-mode') }}</dt><dd class="font-semibold" dir="ltr">{{ __('hr_attendance.devices.setup-mode-value') }}</dd></div>
                <div><dt class="text-sm {{ $muted }}">{{ __('hr_attendance.devices.setup-domain') }}</dt><dd class="font-semibold">{{ __('hr_attendance.devices.on') }}</dd></div>
                <div><dt class="text-sm {{ $muted }}">{{ __('hr_attendance.devices.setup-address') }}</dt><dd class="font-semibold tabular-nums" dir="ltr">{{ $host ?: '—' }}</dd></div>
                <div><dt class="text-sm {{ $muted }}">{{ __('hr_attendance.devices.setup-port') }}</dt><dd class="font-semibold tabular-nums">443</dd></div>
                <div><dt class="text-sm {{ $muted }}">{{ __('hr_attendance.devices.setup-https') }}</dt><dd class="font-semibold">{{ __('hr_attendance.devices.on') }}</dd></div>
            </dl>
            <p class="text-sm {{ $muted }}">{{ __('hr_attendance.devices.setup-where') }}</p>
            <p class="text-sm {{ $muted }}">{{ __('hr_attendance.devices.setup-after') }}</p>
        </x-ui.panel>
    </div>
</x-desk.register>
