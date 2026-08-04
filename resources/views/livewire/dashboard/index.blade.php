@php
    $employee = auth()->user()->profile;

    $firstName = $employee?->first_name;

    $contract = $employee?->latestActiveContract();

    $locale = app()->getLocale();
    $titleCol = $locale === 'ar' ? 'title_ar' : 'title_en';

    $positionTitle = $contract?->position?->$titleCol
        ?? $contract?->position?->title_en
        ?? '—';
@endphp

@section('greeting')
    <div class="py-4 pt-24">
        <h1 class="text-secondary-dark font-semibold text-h3">
            @lang('general.greeting') {{ $firstName }},
        </h1>

        <p class="text-secondary-light text-body">
            {{ $positionTitle }}
        </p>
    </div>
@endsection

<div class="space-y-6">

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="flex h-full min-h-0 flex-col gap-6 lg:col-span-2">
            @if($hasLeaves)
                <div class="flex items-center justify-start font-semibold text-white rounded-surface">
                    <livewire:dashboard.my-leaves-card/>
                </div>
            @endif
            <div class="flex min-h-68 flex-1 items-stretch justify-start rounded-xl font-semibold text-white">
                <livewire:dashboard.employee-notes-card :employee="auth()->id()"/>
            </div>
        </div>

        <div class="w-full rounded-surface text-white font-semibold  {{$hasLeaves ? 'h-full' : 'h-68'}}">
            <x-dashboard-cards.team-members-card/>
        </div>


        <div class="flex items-center justify-center h-full row-span-2 font-semibold text-white rounded-surface">
            <x-dashboard-cards.news-card/>
        </div>


        <div class="flex items-center justify-center font-semibold text-white h-70">
            <livewire:dashboard.calendar-card/>
        </div>

        <div class="flex items-center justify-center font-semibold text-white h-70">
            <livewire:dashboard.upcoming-leaves-card/>

        </div>

        <div class="flex items-center justify-center font-semibold text-white h-70">
            <livewire:news.today-events-card/>
        </div>

        <div class="flex items-center justify-center font-semibold text-white h-70">
            <x-dashboard-cards.upcoming-events-card :upcoming-events="$upcomingEvents"/>
        </div>

        <div class="flex items-center justify-center font-semibold text-white h-70">
            <x-dashboard-cards.pending-requests-card :pending-requests="$pendingRequests"/>
        </div>

        <div class="flex items-center justify-center font-semibold text-white h-70">
            <x-dashboard-cards.checklist-items-card :delivered-items="$deliveredItems"/>
        </div>

        <div class="flex items-center justify-center font-semibold text-white h-70">
            <x-dashboard-cards.my-badges-card :my-badges="$myBadges"/>
        </div>
    </div>




</div>
