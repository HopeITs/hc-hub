@php
    use App\Enums\RecruitmentInterviewLocationType;
    use App\Enums\RecruitmentInterviewType;
    use App\Models\RecruitmentInterviewParticipant;
    use App\Services\RecruitmentInterviewSchedulingService;
@endphp
<div class="space-y-6 p-6">
    <div>
        <a href="{{ $interview ? route('recruitment.interviews.show', ['interviewId' => $interview->id]) : route('recruitment.applicants.show', ['applicantId' => $application->applicant_id]) }}"
           wire:navigate class="text-sm text-primary hover:underline">&larr; {{ __('recruitment-applicant.interviews.back') }}</a>
        <h1 class="mt-2 text-h3 font-semibold">
            {{ $interview ? __('recruitment-applicant.interviews.edit_title') : __('recruitment-applicant.interviews.schedule_title') }}
        </h1>
        <p class="mt-1 text-sm text-secondary-medium">
            {{ $application->applicant?->full_name }}
            &middot; {{ $application->campaign?->vacancy?->position?->title ?? __('recruitment-applicant.no_vacancy') }}
            &middot; {{ __('recruitment-applicant.statuses.'.$application->status) }}
        </p>
    </div>

    @error('interview')
        <p role="alert" class="rounded-control border border-primary bg-primary/5 p-3 text-sm text-primary">{{ $message }}</p>
    @enderror

    <form wire:submit="save" class="space-y-6">
        <section class="space-y-4 rounded-surface border border-neutral-200 bg-white p-6">
            <h2 class="text-h4 font-semibold">{{ __('recruitment-applicant.interviews.details') }}</h2>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <x-forms.input-label for="type" required :value="__('recruitment-applicant.interviews.type')" />
                    <select id="type" wire:model="type" class="w-full rounded-control border border-neutral-300 bg-white px-3 py-2 text-body">
                        @foreach (RecruitmentInterviewType::values() as $value)
                            <option value="{{ $value }}">{{ __('recruitment-applicant.interviews.types.'.$value) }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-secondary-medium">{{ __('recruitment-applicant.interviews.type_hint') }}</p>
                    <x-forms.input-error field="type" input-id="type" />
                </div>

                <div>
                    <x-forms.input-label for="startsAt" required :value="__('recruitment-applicant.interviews.starts_at')" />
                    <x-forms.input id="startsAt" type="datetime-local" model="startsAt" required />
                    <p class="mt-1 text-xs text-secondary-medium">{{ __('recruitment-applicant.interviews.times_in', ['timezone' => $timezone]) }}</p>
                    <x-forms.input-error field="startsAt" input-id="startsAt" />
                </div>

                <div>
                    <x-forms.input-label for="duration" required :value="__('recruitment-applicant.interviews.duration')" />
                    <select id="duration" wire:model="duration" class="w-full rounded-control border border-neutral-300 bg-white px-3 py-2 text-body">
                        @foreach ([15, 30, 45, 60, 90, 120, 180, 240] as $minutes)
                            <option value="{{ $minutes }}">{{ __('recruitment-applicant.interviews.minutes', ['count' => $minutes]) }}</option>
                        @endforeach
                    </select>
                    <x-forms.input-error field="duration" input-id="duration" />
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <x-forms.input-label for="locationType" required :value="__('recruitment-applicant.interviews.location')" />
                    <select id="locationType" wire:model.live="locationType" class="w-full rounded-control border border-neutral-300 bg-white px-3 py-2 text-body">
                        @foreach (RecruitmentInterviewLocationType::values() as $value)
                            <option value="{{ $value }}">{{ __('recruitment-applicant.interviews.location_types.'.$value) }}</option>
                        @endforeach
                    </select>
                    <x-forms.input-error field="locationType" input-id="locationType" />
                </div>

                <div class="md:col-span-2">
                    @if ($locationType === RecruitmentInterviewLocationType::Online)
                        <x-forms.input-label for="meetingUrl" required :value="__('recruitment-applicant.interviews.meeting_url')" />
                        <x-forms.input id="meetingUrl" type="url" model="meetingUrl" placeholder="https://" required />
                        <p class="mt-1 text-xs text-secondary-medium">{{ __('recruitment-applicant.interviews.meeting_url_hint') }}</p>
                        <x-forms.input-error field="meetingUrl" input-id="meetingUrl" />

                        <x-forms.input-label for="locationValue" class="mt-3" :value="__('recruitment-applicant.interviews.platform')" />
                        <x-forms.input id="locationValue" model="locationValue" :placeholder="__('recruitment-applicant.interviews.platform_placeholder')" />
                    @else
                        <x-forms.input-label for="locationValue" required :value="__('recruitment-applicant.interviews.address')" />
                        <x-forms.input id="locationValue" model="locationValue" required />
                    @endif
                    <x-forms.input-error field="locationValue" input-id="locationValue" />
                </div>
            </div>
        </section>

        <section class="space-y-4 rounded-surface border border-neutral-200 bg-white p-6">
            <div>
                <h2 class="text-h4 font-semibold">{{ __('recruitment-applicant.interviews.participants') }}</h2>
                <p class="mt-1 text-sm text-secondary-medium">{{ __('recruitment-applicant.interviews.participants_hint') }}</p>
            </div>

            <div class="grid grid-cols-1 items-end gap-3 md:grid-cols-[1fr_12rem_auto]" wire:key="participant-picker">
                <div>
                    <x-forms.input-label for="newParticipantId" :value="__('recruitment-applicant.interviews.add_participant')" />
                    <x-forms.select2-input wire:model="newParticipantId" id="newParticipantId">
                        <option value="">{{ __('recruitment.choose') }}</option>
                        @foreach ($candidates as $user)
                            <option value="{{ $user->id }}">{{ $user->profile?->name }} — {{ $user->email }}</option>
                        @endforeach
                    </x-forms.select2-input>
                </div>
                <div>
                    <x-forms.input-label for="newParticipantRole" :value="__('recruitment-applicant.interviews.participant_role')" />
                    <select id="newParticipantRole" wire:model="newParticipantRole" class="w-full rounded-control border border-neutral-300 bg-white px-3 py-2 text-body">
                        @foreach (RecruitmentInterviewParticipant::roles() as $role)
                            <option value="{{ $role }}">{{ __('recruitment-applicant.interviews.roles.'.$role) }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="button" wire:click="addParticipant" class="rounded-control border border-primary px-4 py-2 text-sm font-semibold text-primary hover:bg-primary/5">
                    {{ __('recruitment-applicant.interviews.add') }}
                </button>
            </div>
            <x-forms.input-error field="participants" input-id="newParticipantId" />

            @if ($participants === [])
                <p class="text-sm text-secondary-medium">{{ __('recruitment-applicant.interviews.no_participants') }}</p>
            @else
                <ul class="divide-y divide-neutral-100 text-sm">
                    @foreach ($participants as $userId => $role)
                        @php $user = $chosen->get((int) $userId); @endphp
                        <li class="flex flex-wrap items-center justify-between gap-3 py-2" wire:key="participant-{{ $userId }}">
                            <div>
                                <span class="font-medium">{{ $user?->profile?->name ?? $user?->email ?? '#'.$userId }}</span>
                                <span class="text-secondary-medium">&middot; {{ __('recruitment-applicant.interviews.roles.'.$role) }}</span>
                                @unless (RecruitmentInterviewSchedulingService::isActiveUser($user))
                                    <span class="ms-2 rounded-control bg-red-50 px-2 py-0.5 text-xs font-semibold text-red-700">{{ __('recruitment-applicant.interviews.inactive') }}</span>
                                @endunless
                            </div>
                            <button type="button" wire:click="removeParticipant({{ (int) $userId }})" class="text-primary hover:underline">{{ __('recruitment-applicant.interviews.remove') }}</button>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="space-y-4 rounded-surface border border-neutral-200 bg-white p-6">
            @if ($interview)
                <div>
                    <x-forms.input-label for="reason" :value="__('recruitment-applicant.interviews.reschedule_reason')" />
                    <x-forms.textarea id="reason" wire:model="reason" rows="3" />
                    <p class="mt-1 text-xs text-secondary-medium">{{ __('recruitment-applicant.interviews.reschedule_reason_hint', ['min' => RecruitmentInterviewSchedulingService::MIN_REASON]) }}</p>
                    <x-forms.input-error field="reason" input-id="reason" />
                </div>
            @endif

            <label class="flex items-start gap-2 text-sm">
                <input type="checkbox" wire:model="notifyCandidate" class="mt-0.5 size-4 rounded-inset border-neutral-300 text-primary focus:ring-primary">
                <span>
                    {{ $interview ? __('recruitment-applicant.interviews.notify_candidate_change') : __('recruitment-applicant.interviews.notify_candidate') }}
                    <span class="block text-xs text-secondary-medium">{{ __('recruitment-applicant.interviews.notify_candidate_hint') }}</span>
                </span>
            </label>

            <div class="flex justify-end gap-3">
                <x-primary-button type="submit" wire:loading.attr="disabled">
                    {{ $interview ? __('recruitment-applicant.interviews.save_changes') : __('recruitment-applicant.interviews.schedule') }}
                </x-primary-button>
            </div>
        </section>
    </form>
</div>
