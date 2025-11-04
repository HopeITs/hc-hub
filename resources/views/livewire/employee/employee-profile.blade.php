<div class="min-h-screen bg-[var(--color-neutral-100)] py-8 px-6 font-sans text-[var(--color-neutral-800)]">
    <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8">

        <div class="lg:col-span-4">
            <div class="bg-white border border-[var(--color-neutral-200)] shadow-md rounded-2xl overflow-hidden">
                <div class="p-8 flex flex-col items-center text-center relative">
                    @if($employee->getFirstMediaUrl('avatar'))
                        <img src="{{$employee->getFirstMediaUrl('avatar')}}"
                             class="w-28 h-28 rounded-full object-cover border-4 border-[var(--color-primary)] shadow-sm transition-all duration-300 hover:scale-105">
                    @else
                        <div class="w-28 h-28 flex items-center justify-center text-[var(--color-primary)] text-3xl font-bold border-4 border-[var(--color-primary)] rounded-full">
                            {{ strtoupper(substr($employee->name_en,0,2)) }}
                        </div>
                    @endif

                    <h1 class="mt-4 text-xl font-semibold text-[var(--color-secondary-dark)] tracking-tight">{{$employee->name_en}}</h1>
                    <p class="text-[var(--color-primary)] text-sm font-medium">{{$employee->positions()->latest()->first()->title ?? '—'}}</p>
                    <p class="text-[var(--color-secondary-light)] text-xs mt-1">Employee ID: {{$employee->id}}</p>
                </div>

                <div class="border-t border-[var(--color-neutral-200)] px-8 py-6 space-y-6 text-sm">
                    <div>
                        <h2 class="uppercase text-[var(--color-secondary-medium)] text-[10px] font-semibold mb-2 tracking-widest">Contact</h2>
                        <ul class="space-y-1">
                            <li><span class="font-medium">Email:</span> {{$employee->personal_email ?? 'N/A'}}</li>
                            <li><span class="font-medium">Phone:</span> {{$employee->personal_phone ?? 'N/A'}}</li>
                            <li><span class="font-medium">Address:</span> {{$employee->address_ar ?? 'N/A'}}</li>
                        </ul>
                    </div>

                    <div>
                        <h2 class="uppercase text-[var(--color-secondary-medium)] text-[10px] font-semibold mb-2 tracking-widest">National Details</h2>
                        <ul class="space-y-1">
                            <li><span class="font-medium">National ID:</span> {{$employee->national_number ?? 'N/A'}}</li>
                            <li><span class="font-medium">Nationality:</span> {{$employee->nationality ?? 'N/A'}}</li>
                            <li><span class="font-medium">Marital Status:</span> {{$employee->marital_status ?? 'N/A'}}</li>
                        </ul>
                    </div>

                    <div>
                        <h2 class="uppercase text-[var(--color-secondary-medium)] text-[10px] font-semibold mb-2 tracking-widest">Civil Registry</h2>
                        <ul class="space-y-1">
                            <li><span class="font-medium">Office:</span> {{$employee->civil_registry_office ?? 'N/A'}}</li>
                            <li><span class="font-medium">Registry No:</span> {{$employee->civil_registry_number ?? 'N/A'}}</li>
                            <li><span class="font-medium">Place of Birth:</span> {{$employee->place_and_birth_date ?? 'N/A'}}</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-8 space-y-6">

            <div class="bg-white border border-[var(--color-neutral-200)] shadow-md rounded-2xl p-8 hover:shadow-lg transition-shadow duration-300">
                <h2 class="text-lg font-bold text-[var(--color-secondary-dark)] mb-3 flex items-center gap-2">
                    <span class="inline-block w-1.5 h-5 bg-[var(--color-primary)] rounded"></span>
                    About {{$employee->name_en}}
                </h2>
                <p class="text-[var(--color-secondary-medium)] text-sm leading-relaxed">
                    {{$employee->about ?? 'This employee has not yet added a bio. Add one to tell their story, experience, and achievements.'}}
                </p>
            </div>

            <div class="bg-[var(--color-neutral-50)] border border-[var(--color-neutral-200)] rounded-2xl shadow-sm p-8">
                <h2 class="text-lg font-bold text-[var(--color-primary)] mb-5 flex items-center gap-2">
                    <span class="inline-block w-1.5 h-5 bg-[var(--color-primary)] rounded"></span>
                    Personal Information
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-3 text-sm">
                    <p><span class="font-medium">Birth Date:</span> {{ $employee->actual_dob ?? 'N/A' }}</p>
                    <p><span class="font-medium">Years of Service:</span> {{ $employee->years_of_service ?? 'N/A' }}</p>
                    <p><span class="font-medium">City ID:</span> {{ $employee->city_id ?? 'N/A' }}</p>
                    <p><span class="font-medium">Work Phone:</span> {{ $employee->work_phone ?? 'N/A' }}</p>
                    <p><span class="font-medium">Board Member:</span> {{ $employee->is_board_member ? 'Yes' : 'No' }}</p>
                    <p><span class="font-medium">Board Since:</span> {{ $employee->board_member_started_at ?? '—' }}</p>
                </div>
            </div>

            <div class="bg-white border border-[var(--color-neutral-200)] rounded-2xl shadow-md p-8">
                <h2 class="text-lg font-bold text-[var(--color-secondary-dark)] mb-4 flex items-center gap-2">
                    <span class="inline-block w-1.5 h-5 bg-[var(--color-secondary-medium)] rounded"></span>
                    Family Details
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-3 text-sm">
                    <p><span class="font-medium">Father’s Name:</span> {{$employee->father_name_ar ?? 'N/A'}}</p>
                    <p><span class="font-medium">Mother’s Name:</span> {{$employee->mother_name_ar ?? 'N/A'}}</p>
                </div>
            </div>

            @if($employee->skills || $employee->interests)
                <div class="bg-white border border-[var(--color-neutral-200)] rounded-2xl shadow-md p-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        @isset($employee->skills)
                            <div>
                                <h2 class="text-lg font-bold text-[var(--color-secondary-dark)] mb-3 flex items-center gap-2">
                                    <span class="inline-block w-1.5 h-5 bg-[var(--color-primary)] rounded"></span>
                                    Skills
                                </h2>
                                <div class="flex flex-wrap gap-2">
                                    @foreach(json_decode($employee->skills) as $skill)
                                        <span class="px-3 py-1 text-xs font-medium bg-[var(--color-primary-50)] text-[var(--color-primary-700)] border border-[var(--color-primary-200)] rounded-full hover:bg-[var(--color-primary-100)] transition">
                                            {{$skill}}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endisset

                        @isset($employee->interests)
                            <div>
                                <h2 class="text-lg font-bold text-[var(--color-secondary-dark)] mb-3 flex items-center gap-2">
                                    <span class="inline-block w-1.5 h-5 bg-[var(--color-secondary-medium)] rounded"></span>
                                    Interests
                                </h2>
                                <div class="flex flex-wrap gap-2">
                                    @foreach(json_decode($employee->interests) as $interest)
                                        <span class="px-3 py-1 text-xs font-medium bg-[var(--color-neutral-100)] text-[var(--color-secondary-dark)] border border-[var(--color-neutral-200)] rounded-full hover:bg-[var(--color-neutral-200)] transition">
                                            {{$interest}}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endisset
                    </div>
                </div>
            @endif

            <div class="bg-white border border-[var(--color-neutral-200)] rounded-2xl shadow-md p-8">
                <h2 class="text-lg font-bold text-[var(--color-secondary-dark)] mb-8 flex items-center gap-2">
                    <span class="inline-block w-1.5 h-5 bg-[var(--color-primary)] rounded"></span>
                    Experience Timeline
                </h2>

                <div class="relative pl-8 before:content-[''] before:absolute before:top-0 before:left-3 before:w-[2px] before:h-full before:bg-[var(--color-neutral-200)]">

                    <div class="relative mb-10 last:mb-0 group">
                        <div class="absolute -left-[7px] top-1 w-4 h-4 rounded-full border-[3px] border-white bg-[var(--color-primary)] shadow-sm group-hover:scale-110 transition-transform duration-300"></div>

                        <div class="ml-6 bg-[var(--color-neutral-50)] border border-[var(--color-neutral-200)] rounded-xl px-5 py-4 shadow-sm hover:shadow-md transition">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                                <h3 class="text-[var(--color-secondary-dark)] font-semibold text-sm sm:text-base">
                                    Web Developer at ABC Company
                                </h3>
                                <span class="text-[var(--color-secondary-medium)] text-xs sm:text-sm">2017 – 2019</span>
                            </div>
                            <p class="mt-2 text-[var(--color-secondary-light)] text-sm leading-relaxed">
                                Developed and maintained enterprise-level web systems with Laravel and Vue.js.
                            </p>
                        </div>
                    </div>

                    <div class="relative mb-10 last:mb-0 group">
                        <div class="absolute -left-[7px] top-1 w-4 h-4 rounded-full border-[3px] border-white bg-[var(--color-primary)] shadow-sm group-hover:scale-110 transition-transform duration-300"></div>
                        <div class="ml-6 bg-[var(--color-neutral-50)] border border-[var(--color-neutral-200)] rounded-xl px-5 py-4 shadow-sm hover:shadow-md transition">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                                <h3 class="text-[var(--color-secondary-dark)] font-semibold text-sm sm:text-base">
                                    Frontend Engineer at TechNova
                                </h3>
                                <span class="text-[var(--color-secondary-medium)] text-xs sm:text-sm">2019 – Present</span>
                            </div>
                            <p class="mt-2 text-[var(--color-secondary-light)] text-sm leading-relaxed">
                                Built scalable front-end architecture and refined UI systems across web platforms.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
