<div class="w-full h-full  bg-white rounded-2xl shadow ring-1 ring-gray-100 overflow-hidden">
    <div class="bg-primary text-white px-6 py-5 flex items-center gap-4">
        <img
            src="{{ $employee?->avatar_url ?: asset('images/default-avatar.png') }}"
            alt="Employee Avatar"
            class="h-16 w-16 rounded-full object-cover">

        <div>
            <h2 class="text-lg font-semibold leading-tight">
                {{ $employee?->name_en ?? 'Employee Name' }}
            </h2>

            <p class="text-sm text-gray-300">
                {{ $positionTitle }}
                @if($departmentName && $departmentName !== '—')
                    <span class="text-gray-400">· {{ $departmentName }}</span>
                @endif
            </p>
        </div>
    </div>

    <div class="px-6 py-4 text-gray-700 divide-y divide-gray-100">
        <div class="pb-2">
            <p class="text-xs uppercase text-gray-500 mb-1">Phone Number</p>
            <p class="font-medium">{{ $employee?->personal_phone ?? '—' }}</p>
        </div>

        <div class="py-2">
            <p class="text-xs uppercase text-gray-500 mb-1">Work Email</p>
            <p class="font-medium break-all">{{ $workEmail }}</p>
        </div>

        <div class="py-2">
            <p class="text-xs uppercase text-gray-500 mb-1">City</p>
            <p class="font-medium">{{ $employee?->city?->name ?? '—' }}</p>
        </div>

        <div class="pt-2">
            <p class="text-xs uppercase text-gray-500 mb-1">Joined On</p>
            <p class="font-medium">{{ $joinedOn }}</p>
        </div>
    </div>
</div>
