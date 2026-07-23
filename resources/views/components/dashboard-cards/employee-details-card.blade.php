<div class="w-full h-full  bg-white rounded-surface shadow-e1 ring-1 ring-neutral-100 overflow-hidden">
    <div class="bg-primary text-white px-6 py-4 flex items-center gap-4">
        <img
            src="{{ $employee?->getFirstMediaUrl('avatar') ?: 'https://ui-avatars.com/api/?name=' . urlencode($employee?->name_en ?? 'Employee') }}"
            alt="Employee Avatar"
            class="h-16 w-16 rounded-full object-cover">

        <div>
            <h2 class="text-h4 font-semibold leading-tight">
                {{ $employee?->name_en ?? 'Employee Name' }}
            </h2>

            <p class="text-body text-secondary-light">
                {{ $positionTitle }}
                @if($departmentName && $departmentName !== '—')
                    <span class="text-secondary-light">· {{ $departmentName }}</span>
                @endif
            </p>
        </div>
    </div>

    <div class="px-6 py-4 text-neutral-800 divide-y divide-neutral-100">
        <div class="pb-2">
            <p class="text-small uppercase text-secondary-medium mb-1">Phone Number</p>
            <p class="font-medium">{{ $employee?->personal_phone ?? '—' }}</p>
        </div>

        <div class="py-2">
            <p class="text-small uppercase text-secondary-medium mb-1">Work Email</p>
            <p class="font-medium break-all">{{ $workEmail }}</p>
        </div>

        <div class="py-2">
            <p class="text-small uppercase text-secondary-medium mb-1">City</p>
            <p class="font-medium">{{ $employee?->city?->name ?? '—' }}</p>
        </div>

        <div class="pt-2">
            <p class="text-small uppercase text-secondary-medium mb-1">Joined On</p>
            <p class="font-medium">{{ $joinedOn }}</p>
        </div>
    </div>
</div>
