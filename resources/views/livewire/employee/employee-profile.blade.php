<div class="min-h-screen pb-4 px-6 bg-[var(--color-neutral-50)] font-sans text-[var(--color-neutral-800)]">
    <div class="max-w-7xl mx-auto">

        {{-- ============ GRID ============ --}}
        <div class="grid grid-cols-3 grid-rows-4 gap-4">

            {{-- CARD 0 — MAIN PROFILE --}}
            <div class="row-span-2 bg-white border border-[var(--color-neutral-200)] shadow-md rounded-2xl p-8 flex flex-col items-center text-center">
                @if($employee->getFirstMediaUrl('avatar'))
                    <img src="{{$employee->getFirstMediaUrl('avatar')}}"
                         class="w-28 h-28 rounded-full object-cover border-4 border-[var(--color-primary)] shadow-sm">
                @else
                    <div class="w-28 h-28 flex items-center justify-center text-[var(--color-primary)] text-3xl font-bold border-4 border-[var(--color-primary)] rounded-full">
                        {{ strtoupper(substr($employee->name_en,0,2)) }}
                    </div>
                @endif

                <h1 class="mt-4 text-xl font-semibold text-[var(--color-secondary-dark)] tracking-tight">
                    {{$employee->name_en}}
                </h1>
                <p class="text-[var(--color-primary)] text-sm font-medium">
                    {{$employee->positions()->latest()->first()->title ?? '—'}}
                </p>
                <p class="text-[var(--color-secondary-light)] text-xs">
                    Employee ID: {{$employee->id}}
                </p>
            </div>

            {{-- CARD 6 — POSITION ROW (spans 2 columns) --}}
            <div class="col-span-2 col-start-2 row-start-1 bg-white border border-[var(--color-neutral-200)] shadow-md rounded-2xl p-8">
                <h2 class="text-lg font-bold text-[var(--color-secondary-dark)] mb-3 flex items-center gap-2">
                    <span class="inline-block w-1.5 h-5 bg-[var(--color-primary)] rounded"></span>
                    Employment Overview
                </h2>
                <ul class="text-sm space-y-1">
                    <li><strong>Name:</strong> {{ $employee->name_en }}</li>
                    <li><strong>Years of Service:</strong> {{ $employee->years_of_service ?? 'N/A' }}</li>
                    <li><strong>Position:</strong> {{ $employee->positions()->latest()->first()->title ?? '—' }}</li>
                    <li><strong>Department:</strong> {{ $employee->department->name ?? '—' }}</li>
                    <li><strong>Center:</strong> {{ $employee->center->name ?? '—' }}</li>
                </ul>
            </div>

            {{-- CARD 1 — BIO --}}
            <div class="col-start-2 row-start-2 bg-white border border-[var(--color-neutral-200)] shadow-md rounded-2xl p-8">
                <h2 class="text-lg font-bold text-[var(--color-secondary-dark)] mb-3 flex items-center gap-2">
                    <span class="inline-block w-1.5 h-5 bg-[var(--color-primary)] rounded"></span>
                    Bio & Birthday
                </h2>
                <p class="text-sm text-[var(--color-secondary-medium)]">
                    {{ $employee->about ?? 'No bio added yet.' }}
                </p>
                <p class="mt-3 text-sm"><strong>Birthday:</strong> {{ $employee->actual_dob ?? 'N/A' }}</p>
            </div>

            {{-- CARD 3 — CONTACT --}}
            <div class="col-start-3 row-start-2 bg-white border border-[var(--color-neutral-200)] shadow-md rounded-2xl p-8">
                <h2 class="text-lg font-bold text-[var(--color-secondary-dark)] mb-3 flex items-center gap-2">
                    <span class="inline-block w-1.5 h-5 bg-[var(--color-primary)] rounded"></span>
                    Contact
                </h2>

                <ul class="space-y-1 text-sm">
                    <li><strong>Email:</strong> {{$employee->personal_email ?? 'N/A'}}</li>
                    <li><strong>Phone:</strong> {{$employee->personal_phone ?? 'N/A'}}</li>
                </ul>
            </div>

            {{-- CARD 2 --}}
            <div class="col-start-2 row-start-3 bg-white border border-[var(--color-neutral-200)] shadow-md rounded-2xl p-8">
                <h2 class="text-lg font-bold text-[var(--color-secondary-dark)] mb-3 flex items-center gap-2">
                    <span class="inline-block w-1.5 h-5 bg-[var(--color-primary)] rounded"></span>
                    Skills & Interests
                </h2>

                <div class="flex flex-wrap gap-2">
                    @foreach(json_decode($employee->skills ?? '[]') as $skill)
                        <span class="px-3 py-1 text-xs font-medium bg-[var(--color-primary)]/10 text-[var(--color-primary)] border border-[var(--color-primary)]/20 rounded-full">
                            {{ $skill }}
                        </span>
                    @endforeach

                    @foreach(json_decode($employee->interests ?? '[]') as $interest)
                        <span class="px-3 py-1 text-xs font-medium bg-[var(--color-neutral-100)] text-[var(--color-secondary-dark)] border border-[var(--color-neutral-200)] rounded-full">
                            {{ $interest }}
                        </span>
                    @endforeach
                </div>
            </div>

            {{-- CARD 4 --}}
            <div class="col-start-3 row-start-3 bg-white border border-[var(--color-neutral-200)] shadow-md rounded-2xl p-8">
                <h2 class="text-lg font-bold text-[var(--color-secondary-dark)] mb-3 flex items-center gap-2">
                    <span class="inline-block w-1.5 h-5 bg-[var(--color-secondary-medium)] rounded"></span>
                    Personal Life
                </h2>

                <ul class="space-y-1 text-sm">
                    <li><strong>Marital Status:</strong> {{ $employee->marital_status ?? 'N/A' }}</li>
                    <li><strong>Children:</strong> {{ $employee->children ?? 'N/A' }}</li>
                    <li><strong>Address:</strong> {{ $employee->address_ar ?? 'N/A' }}</li>

                    @if($employee->relatives)
                        <li class="pt-2"><strong>Relatives:</strong></li>
                        @foreach($employee->relatives as $relative)
                            <p class="text-xs text-[var(--color-secondary-light)]">• {{ $relative->name }} ({{ $relative->relation }})</p>
                        @endforeach
                    @endif
                </ul>
            </div>

            {{-- CARD 7 --}}
            <div class="row-span-2 col-start-1 row-start-3 bg-white border border-[var(--color-neutral-200)] shadow-md rounded-2xl p-8">
                <h2 class="text-lg font-bold text-[var(--color-secondary-dark)] mb-3 flex items-center gap-2">
                    <span class="inline-block w-1.5 h-5 bg-[var(--color-primary)] rounded"></span>
                    National ID
                </h2>

                <ul class="text-sm space-y-1">
                    <li><strong>National ID:</strong> {{ $employee->national_number }}</li>
                    <li><strong>Office:</strong> {{ $employee->civil_registry_office }}</li>
                    <li><strong>Registry No:</strong> {{ $employee->civil_registry_number }}</li>
                    <li><strong>Birth Place:</strong> {{ $employee->place_and_birth_date }}</li>
                    <li><strong>Nationality:</strong> {{ $employee->nationality }}</li>
                </ul>
            </div>

            {{-- CARD 8 --}}
            <div class="col-start-2 bg-white border border-[var(--color-neutral-200)] shadow-md rounded-2xl p-8">
                <h2 class="text-lg font-bold text-[var(--color-secondary-dark)] mb-3 flex items-center gap-2">
                    <span class="inline-block w-1.5 h-5 bg-[var(--color-primary)] rounded"></span>
                    Badges
                </h2>
                <p class="text-sm text-[var(--color-secondary-light)]">Badge list…</p>
            </div>

            {{-- CARD 9 --}}
            <div class="col-start-3 row-start-4 bg-white border border-[var(--color-neutral-200)] shadow-md rounded-2xl p-8">
                <h2 class="text-lg font-bold text-[var(--color-secondary-dark)] mb-3 flex items-center gap-2">
                    <span class="inline-block w-1.5 h-5 bg-[var(--color-primary)] rounded"></span>
                    Attachments
                </h2>
                <p class="text-sm text-[var(--color-secondary-light)]">File attachments…</p>
            </div>

        </div>

        {{-- CARD 10 --}}
        <div class=" bg-white border mt-4 border-[var(--color-neutral-200)] shadow-md rounded-3xl p-8">
            <h2 class="text-lg font-bold text-[var(--color-secondary-dark)] mb-3 flex items-center gap-2">
                <span class="inline-block w-1.5 h-5 bg-[var(--color-primary)] rounded"></span>
                Contract Information
            </h2>

            <ul class="space-y-1 text-sm">
                <li><strong>Contract Type:</strong> {{ $employee->contract->type ?? '—' }}</li>
                <li><strong>Board Since:</strong> {{ $employee->board_member_started_at ?? '—' }}</li>
            </ul>
        </div>

    </div>
</div>
