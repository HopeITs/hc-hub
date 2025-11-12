@section('greeting')
    <div class="flex justify-start py-4">
        <h1 class="text-secondary-dark font-bold" >{{'Hello, '. auth()->user()->profile->name }}</h1>
    </div>

@endsection
<div class="space-y-10">

    <div class="grid grid-cols-3 gap-6">
        <div class="col-span-2 grid grid-rows-2 gap-6 h-full">
            <div class=" rounded-xl flex items-center justify-start text-white font-semibold h-68">
                <x-dashboard-cards.my-leaves-card />
            </div>

            <div class=" rounded-xl flex items-center justify-start text-white font-semibold h-68">
                <livewire:dashboards.employee-notes-card :employee="auth()->id()" />
            </div>
        </div>

        <div class="rounded-xl flex items-center justify-center text-white font-semibold h-full">
            <x-dashboard-cards.team-members-card />
        </div>
    </div>


    <div class="grid grid-cols-3 gap-6">

        <div class=" rounded-xl flex items-center justify-center text-white font-semibold row-span-2 h-full">
            <x-dashboard-cards.news-card/>
        </div>

        <div class="h-70 flex items-center justify-center text-white font-semibold">
            <x-dashboard-cards.today-events-card/>

        </div>

        <div class="h-70 flex items-center justify-center text-white font-semibold">
            <x-dashboard-cards.upcoming-events-card/>

        </div>

        <div class=" h-70 flex items-center justify-center text-white font-semibold">
            <x-dashboard-cards.upcoming-holidays-card/>
        </div>

        <div class=" h-70 flex items-center justify-center text-white font-semibold">
            <x-dashboard-cards.upcoming-leaves-card/>
        </div>
    </div>

    <div class="grid grid-cols-3 gap-6">
        <div class="h-70 flex items-center justify-center text-white font-semibold">
            <x-dashboard-cards.pending-requests-card :pending-requests="$pendingRequests"/>
        </div>

        <div class="h-70 flex items-center justify-center text-white font-semibold">
            <x-dashboard-cards.checklist-items-card :delivered-items="$deliveredItems"/>
        </div>

        <div class="h-70 flex items-center justify-center text-white font-semibold">
            <x-dashboard-cards.my-badges-card :my-badges="$myBadges"/>
        </div>
    </div>

</div>
