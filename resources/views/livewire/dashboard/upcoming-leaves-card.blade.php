@php
    use App\Enums\LeaveRequestStatus;
@endphp
<div
        class="w-full h-full  bg-white rounded-surface shadow-[0px_0px_25px_-14px_rgba(88,_89,_91,_0.30)] ring-1 ring-[var(--color-neutral-200)] overflow-hidden flex flex-col">
    <x-dashboard-cards.card-header :title="__('general.upcoming-leaves')" :count="$upcomingLeaves->count()">
        <x-slot:icon>
            <img class="block size-full object-contain" src="{{ asset('assets/icons/upcoming-leaves.svg') }}" alt="">
        </x-slot:icon>
    </x-dashboard-cards.card-header>

    <!-- Scrollable Content -->
    <div
            class="flex-1 min-h-0 overflow-y-auto px-6 pb-6 space-y-3 text-start scrollbar-thin scrollbar-thumb-[var(--color-secondary-medium)] scrollbar-track-[var(--color-neutral-100)] hover:scrollbar-thumb-[var(--color-secondary-medium)]">
        <!-- Leave Item -->

        @forelse($upcomingLeaves as $upcomingLeave)
            <div class="flex items-start gap-3 rounded-control border border-neutral-200 bg-neutral-50 p-4">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-control border border-neutral-200 bg-white text-secondary-medium" aria-hidden="true">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="5" width="18" height="16" rx="2" />
                        <path d="M16 3v4M8 3v4M3 11h18M8 15h2M14 15h2M8 18h2" />
                    </svg>
                </span>
                <div class="min-w-0 flex-1">
                <div class="flex flex-wrap justify-between items-center gap-x-3 gap-y-2">
                    <p class="min-w-0 break-words text-body font-semibold leading-relaxed text-secondary-dark">{{$upcomingLeave->leaveBalance->contract->employee->name}}</p>
                    @activerole('HR','super-admin','general-manager','manager')
                    <span
                            class="text-small shrink-0 font-medium leading-5 text-center text-white bg-{{LeaveRequestStatus::getLocalizedName($upcomingLeave->status)['color']}} px-2 py-0.5 rounded-control">{{LeaveRequestStatus::getLocalizedName($upcomingLeave->status)['value']}}</span>
                    @endactiverole
                </div>

                @if($upcomingLeave->type === 'day')
                    @php
                        if($upcomingLeave->start_at->isSameDay($upcomingLeave->end_at)){
                            if($upcomingLeave->start_at->isToday()){
                            $leave_date = '<span class="me-1">'.__('general.today').'</span>';
                            }elseif($upcomingLeave->start_at->isTomorrow()){
                            $leave_date = '<span class="me-1">'.__('general.tomorrow').'</span>';
                            }else{
                            $leave_date = $upcomingLeave->start_at->format('d M');}
                        }else{
                            $leave_date ='<span>' . $upcomingLeave->start_at->format('d M') .  '</span><span class="me-1"> - '.$upcomingLeave->end_at->format('d M') .'</span>';
                        }
                    @endphp
                    <p class="text-small font-normal mt-1 text-secondary-medium leading-relaxed gap-1 flex flex-wrap">{!! $leave_date . ' ('.$upcomingLeave->leaveBalance->leaveType->name .') ' !!}</p>

                @else
                    @php
                        if($upcomingLeave->start_at->isToday()){
                            $leave_date = '<span class="me-1">'.__('general.today').'</span><span>' .$upcomingLeave->start_at->format('H:i') . '</span><span> - ' .$upcomingLeave->end_at->format('H:i') .'</span>' ;
                        }elseif($upcomingLeave->start_at->isTomorrow()){
                            $leave_date = '<span class="me-1">'.__('general.tomorrow').'</span><span>' .$upcomingLeave->start_at->format('H:i') . '</span><span> - ' .$upcomingLeave->end_at->format('H:i') .'</span>';
                        }else{
                            $leave_date = $upcomingLeave->start_at->format('d M').'<span>' .$upcomingLeave->start_at->format('H:i') . '</span><span> - ' .$upcomingLeave->end_at->format('H:i') .'</span>' ;
                        }

                    @endphp
                    <p class="text-small font-normal mt-1 text-secondary-medium leading-relaxed break-words">{!!   $leave_date . ' ('.$upcomingLeave->leaveBalance->leaveType->name .') '  !!}
                    </p>
                @endif
                </div>
            </div>
        @empty
            <div class="flex flex-col items-center justify-center text-body font-normal text-secondary-medium h-full">
                <span class="text-small">@lang('general.no-available-data')</span>
            </div>
        @endforelse

    </div>
</div>
