@php
    $nameField = 'name_' . app()->getLocale();

    $maritalStatuses = [
        0 => __('general.unknown'),
        1 => __('general.single'),
        2 => __('general.engaged'),
        3 => __('general.married'),
        4 => __('general.divorced'),
        5 => __('general.widowed'),
        6 => __('general.separated'),
    ];
@endphp

<div class="min-h-screen  pb-4 px-6 bg-neutral-50 font-sans text-secondary-dark">
    <div class="max-w-7xl  mx-auto">

        <div class="grid  grid-cols-1 md:grid-cols-2 lg:grid-cols-3 auto-rows-auto gap-4">

            <div class="lg:row-span-2 bg-white border border-neutral-200 shadow-[0px_0px_25px_-14px_rgba(0,_0,_0,_0.30)] rounded-2xl p-8 flex flex-col justify-center items-center text-center">
                @if($employee->getFirstMediaUrl('avatar'))
                    <img src="{{$employee->getFirstMediaUrl('avatar')}}"
                         class="w-28 h-28 rounded-full object-cover border-4 border-primary shadow-sm">
                @else
                    <div class="w-28 h-28 flex items-center justify-center text-primary text-3xl font-bold border-4 border-primary rounded-full">
                        {{ strtoupper(substr($employee->$nameField,0,2)) }}
                    </div>
                @endif

                <h1 class="mt-4 text-xl font-semibold text-secondary-dark tracking-tight">
                    {{$employee->$nameField}}
                </h1>
                <p class="mt-2 text-primary text-sm font-medium">
                    {{$employee->positions()->latest()->first()->title ?? '—'}}
                </p>
                <p class="text-secondary-light text-xs mt-2">
                    @lang('general.employee-id'): {{$employee->id}}
                </p>
            </div>

            <div class="lg:col-span-2 lg:col-start-2 lg:row-start-1 bg-white border border-neutral-200 shadow-[0px_0px_25px_-14px_rgba(0,_0,_0,_0.30)] rounded-2xl p-8">
                <h2 class="text-lg font-bold text-secondary-dark mb-3 flex items-center gap-2">
                    <span class="inline-block w-1.5 h-5 bg-primary rounded"></span>
                    @lang('general.employment-overview')
                </h2>
                <ul class="text-sm space-y-1">
                    <li><strong>@lang('general.name'):</strong> {{ $employee->$nameField }}</li>
                    <li><strong>@lang('general.years-of-service'):</strong> {{ $employee->years_of_service ?? 'N/A' }}</li>
                    <li><strong>@lang('general.position'):</strong> {{ $employee->positions()->latest()->first()->title ?? '—' }}</li>
                    <li><strong>{{trans_choice('general.department' , 1)}}:</strong> {{ $employee->department->name ?? '—' }}</li>
                    <li><strong>{{trans_choice('general.center' , 1)}}:</strong> {{ $employee->center->name ?? '—' }}</li>
                </ul>
            </div>

            <div class="lg:col-start-2 lg:row-start-2 bg-white border border-neutral-200 shadow-[0px_0px_25px_-14px_rgba(0,_0,_0,_0.30)] rounded-2xl p-8">
                <h2 class="text-lg font-bold text-secondary-dark mb-3 flex items-center gap-2">
                    <span class="inline-block w-1.5 h-5 bg-primary rounded"></span>
                    @lang('general.bio-birthday')
                </h2>
                <p class="text-sm text-secondary-medium">
                    {{ $employee->about ?? __('general.bio-text') }}
                </p>
                <p class="mt-2 text-sm"><strong>@lang('general.birthday'):</strong> {{ $employee->actual_dob ? \Carbon\Carbon::parse($employee->actual_dob)->format('Y-m-d') : 'N/A' }}
                </p>
            </div>

            <div class="lg:col-start-3 lg:row-start-2 bg-white border border-neutral-200 shadow-[0px_0px_25px_-14px_rgba(0,_0,_0,_0.30)] rounded-2xl p-8">
                <h2 class="text-lg font-bold text-secondary-dark mb-3 flex items-center gap-2">
                    <span class="inline-block w-1.5 h-5 bg-primary rounded"></span>
                    @lang('general.contact')
                </h2>

                <ul class="space-y-1 text-sm">
                    <li><strong>@lang('general.email'):</strong> {{$employee->personal_email ?? 'N/A'}}</li>
                    <li><strong>@lang('general.phone'):</strong> {{$employee->personal_phone ?? 'N/A'}}</li>
                </ul>
            </div>

            <div class="lg:col-start-2 lg:row-start-3 bg-white border border-neutral-200 shadow-[0px_0px_25px_-14px_rgba(0,_0,_0,_0.30)] rounded-2xl p-8">
                <h2 class="text-lg font-bold text-secondary-dark mb-3 flex items-center gap-2">
                    <span class="inline-block w-1.5 h-5 bg-primary rounded"></span>
                    @lang('general.skills-and-interests')
                </h2>

                <div class="flex flex-wrap gap-2">
                    @foreach(json_decode($employee->skills ?? '[]') as $skill)
                        <span class="px-3 py-1 text-xs font-medium bg-primary/10 text-primary border border-primary/20 rounded-full">
                            {{ $skill }}
                        </span>
                    @endforeach

                    @foreach(json_decode($employee->interests ?? '[]') as $interest)
                        <span class="px-3 py-1 text-xs font-medium bg-neutral-100 text-secondary-dark border border-neutral-200 rounded-full">
                            {{ $interest }}
                        </span>
                    @endforeach
                </div>
            </div>

            <div class="lg:col-start-3 lg:row-start-3 bg-white border border-neutral-200 shadow-[0px_0px_25px_-14px_rgba(0,_0,_0,_0.30)] rounded-2xl p-8">
                <h2 class="text-lg font-bold text-secondary-dark mb-3 flex items-center gap-2">
                    <span class="inline-block w-1.5 h-5 bg-primary rounded"></span>
                    @lang('general.marital-status')
                </h2>

                <ul class="space-y-1 text-sm">
                    <li><strong>@lang('general.marital-status'):</strong>{{ $maritalStatuses[$employee->marital_status] ?? 'N/A' }}</li>
                    <li><strong>@lang('general.children'):</strong> {{ $employee->children ?? 'N/A' }}</li>
                    <li><strong>@lang('general.address'):</strong> {{ $employee->address_ar ?? 'N/A' }}</li>

                    @if($employee->relatives)
                        <li><strong>@lang('general.relatives'):</strong></li>
                        @foreach($employee->relatives as $relative)
                            <p class="text-xs text-secondary-light">• {{ $relative->name }} ({{ $relative->relation }})</p>
                        @endforeach
                    @endif
                </ul>
            </div>

            <div class="lg:row-span-2 lg:col-start-1 lg:row-start-3 bg-white border border-neutral-200 shadow-[0px_0px_25px_-14px_rgba(0,_0,_0,_0.30)] rounded-2xl p-8">
                <h2 class="text-lg font-bold text-secondary-dark mb-3 flex items-center gap-2">
                    <span class="inline-block w-1.5 h-5 bg-primary rounded"></span>
                    @lang('general.national-info')
                </h2>

                <ul class="text-sm space-y-1">
                    <li><strong>@lang('general.national-id'):</strong> {{ $employee->national_number }}</li>
                    <li><strong>@lang('general.civil-registry-office'):</strong> {{ $employee->civil_registry_office }}</li>
                    <li><strong>@lang('general.civil-registry-number'):</strong> {{ $employee->civil_registry_number }}</li>
                    <li><strong>@lang('general.birth-place'):</strong> {{ $employee->place_and_birth_date }}</li>
                    <li><strong>@lang('general.nationality'):</strong> {{ $employee->nationality }}</li>
                </ul>
            </div>

            <div class="lg:col-start-2 bg-white border border-neutral-200 shadow-[0px_0px_25px_-14px_rgba(0,_0,_0,_0.30)] rounded-2xl p-8">
                <h2 class="text-lg font-bold text-secondary-dark mb-3 flex items-center gap-2">
                    <span class="inline-block w-1.5 h-5 bg-primary rounded"></span>
                    @lang('general.badges')
                </h2>
            </div>

            <div class="lg:col-start-3 lg:row-start-4 bg-white border border-neutral-200 shadow-[0px_0px_25px_-14px_rgba(0,_0,_0,_0.30)] rounded-2xl p-8">
                <h2 class="text-lg font-bold text-secondary-dark mb-3 flex items-center gap-2">
                    <span class="inline-block w-1.5 h-5 bg-primary rounded"></span>
                    @lang('general.attachments')
                </h2>
            </div>

        </div>

        <div class=" bg-white border mt-4 border-neutral-200 shadow-[0px_0px_25px_-14px_rgba(0,_0,_0,_0.30)] rounded-3xl p-8">
            <h2 class="text-lg font-bold text-secondary-dark mb-3 flex items-center gap-2">
                <span class="inline-block w-1.5 h-5 bg-primary rounded"></span>
                @lang('general.contract-details')
            </h2>

            <ul class="space-y-1 text-sm">
                <li><strong>{{trans_choice('general.contract-type' ,1 )}}:</strong> {{ $employee->contract->type ?? '—' }}</li>
                <li><strong>@lang('general.board-since'):</strong> {{ $employee->board_member_started_at ?? '—' }}</li>
            </ul>
        </div>

    </div>
</div>
