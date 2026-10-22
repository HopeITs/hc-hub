{{--
    Read-only employee drawer (B4). Opened by the browser event `employee-drawer`
    ({ id }) that the list's name button / row click dispatches. Escape, the close
    button and the backdrop close it; focus goes back to the control that opened it.
--}}
<div x-data="{
        trigger: null,
        close() {
            const t = this.trigger;
            $wire.close().then(() => t && t.isConnected && t.focus());
        },
    }"
    x-on:employee-drawer.window="trigger = (document.activeElement && document.activeElement !== document.body) ? document.activeElement : [...document.querySelectorAll('[data-employee-open='' + $event.detail.id + '']')].find(el => el.offsetParent !== null) ?? null; $wire.open($event.detail.id)">
    @if($employee)
        @php
            $contract = $employee->latestActiveContract();
            $position = $contract?->position?->title;
            $manager = $contract?->position?->directManager?->employee()?->name;
            $email = $employee->account?->email;
            $titleId = 'employee-drawer-title-'.$employee->id;
            $fields = [
                [__('general.position'), $position],
                [trans_choice('general.city', 1), $employee->city?->name],
                [__('general.direct_manager'), $manager],
                [__('general.work_email'), $email, 'ltr'],
                [__('general.join-at'), $employee->joined_at?->copy()->locale(app()->getLocale())->translatedFormat('d M Y')],
                [__('general.years-of-service'), $employee->years_of_service_formatted],
            ];
        @endphp
        <div class="fixed inset-0 z-50" wire:key="employee-drawer-{{ $employee->id }}">
            <div class="absolute inset-0 bg-black/40" x-on:click="close()" aria-hidden="true"></div>
            <div role="dialog" aria-modal="true" aria-labelledby="{{ $titleId }}"
                x-trap.noscroll="true"
                x-on:keydown.escape.stop="close()"
                class="absolute inset-y-0 end-0 flex w-full max-w-md flex-col overflow-y-auto bg-white shadow-e3">
                <div class="flex items-start justify-between gap-3 border-b border-[var(--line-hairline)] p-4">
                    <div class="flex min-w-0 items-center gap-3">
                        <img src="{{ $employee->avatar_url }}" alt="" class="size-14 max-w-none shrink-0 rounded-full border border-primary bg-white object-cover" />
                        <div class="min-w-0">
                            <h2 id="{{ $titleId }}" class="break-words text-h4 font-semibold"><bdi>{{ $employee->name }}</bdi></h2>
                            @if($position)<p class="break-words text-small text-secondary-medium"><bdi>{{ $position }}</bdi></p>@endif
                        </div>
                    </div>
                    <button type="button" x-on:click="close()"
                        aria-label="{{ __('general.emp-drawer-close') }}"
                        class="inline-flex min-h-11 min-w-11 shrink-0 items-center justify-center rounded-lg text-secondary-medium hover:text-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                        <span aria-hidden="true" class="text-2xl leading-none">&times;</span>
                    </button>
                </div>

                <dl class="flex flex-col gap-4 p-4">
                    <div>
                        <dt class="text-small text-secondary-medium">{{ __('general.status') }}</dt>
                        <dd class="mt-1">@include('livewire.employee.partials.status', ['employee' => $employee])</dd>
                    </div>
                    @foreach($fields as $field)
                        @continue(blank($field[1]))
                        <div>
                            <dt class="text-small text-secondary-medium">{{ $field[0] }}</dt>
                            <dd class="mt-1 break-words" @if(($field[2] ?? null) === 'ltr') dir="ltr" @endif><bdi>{{ $field[1] }}</bdi></dd>
                        </div>
                    @endforeach
                </dl>

                @if($employee->username)
                    <div class="mt-auto border-t border-[var(--line-hairline)] p-4">
                        <a href="{{ route('employees.show', ['employee' => $employee->username]) }}" wire:navigate
                            class="inline-flex min-h-11 items-center justify-center rounded-lg bg-primary px-5 text-sm font-semibold text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">{{ __('general.view-profile') }}</a>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
