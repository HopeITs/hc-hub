<x-slot:title>{{ __('recruitment.campaign.title') }}</x-slot:title>

<div class="space-y-6">
    <div>
        <h1 class="text-h2 font-semibold text-neutral-900">{{ __('recruitment.campaign.title') }}</h1>
        <p class="mt-2 text-secondary-medium">{{ __('recruitment.campaign.description') }}</p>
    </div>

    @if ($notice || session('campaign_notice'))
        <div class="rounded-control border border-green-200 bg-green-50 p-4 text-body text-green-800" role="status">
            {{ $notice ?? session('campaign_notice') }}
        </div>
    @endif

    @error('campaign')
        <div class="rounded-control border border-red-200 bg-red-50 p-4 text-body text-red-800" role="alert">{{ $message }}</div>
    @enderror

    @activepermission('recruitment-campaigns.manage')
        <section class="rounded-card border border-neutral-200 bg-white p-5 shadow-e1">
            <div class="mb-4">
                <h2 class="text-title font-semibold text-neutral-900">{{ __('recruitment.campaign.create') }}</h2>
                <p class="mt-1 text-body text-neutral-600">{{ __('recruitment.campaign.create_help') }}</p>
            </div>

            @if ($availableVacancies->isEmpty())
                <p class="rounded-control bg-neutral-50 p-4 text-body text-neutral-600">{{ __('recruitment.campaign.no_available_vacancies') }}</p>
            @else
                <form wire:submit="create" class="grid gap-4 lg:grid-cols-[minmax(18rem,1fr)_minmax(13rem,0.45fr)_minmax(13rem,0.45fr)_auto] lg:items-end">
                    <div>
                        <x-forms.input-label for="campaign-vacancy" :value="__('recruitment.campaign.vacancy')" required />
                        <select id="campaign-vacancy" wire:model="vacancyId" required class="mt-1 w-full rounded-control border border-neutral-300 bg-white px-3 py-2 text-body focus:border-primary focus:ring-primary">
                            <option value="">{{ __('recruitment.campaign.choose_vacancy') }}</option>
                            @foreach ($availableVacancies as $vacancy)
                                <option value="{{ $vacancy->id }}">
                                    {{ $vacancy->position?->title ?? $vacancy->position?->title_en }}
                                    — {{ $vacancy->createdFromRequest?->request_number }}
                                    ({{ max(0, $vacancy->needed_count - $vacancy->filled_count) }} {{ __('recruitment.campaign.remaining') }})
                                </option>
                            @endforeach
                        </select>
                        @error('vacancyId') <p class="mt-1 text-small text-red-700">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <x-forms.input-label for="campaign-start" :value="__('recruitment.campaign.starts_at')" required />
                        <x-forms.input id="campaign-start" type="datetime-local" model="startsAt" :required="true" class="mt-1" />
                        @error('startsAt') <p class="mt-1 text-small text-red-700">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <x-forms.input-label for="campaign-end" :value="__('recruitment.campaign.ends_at')" required />
                        <x-forms.input id="campaign-end" type="datetime-local" model="endsAt" :required="true" class="mt-1" />
                        @error('endsAt') <p class="mt-1 text-small text-red-700">{{ $message }}</p> @enderror
                    </div>
                    <x-primary-button wire:target="create">{{ __('recruitment.campaign.create_action') }}</x-primary-button>
                </form>
            @endif
        </section>
    @endactivepermission

    @if ($editingCampaignId)
        <section class="rounded-card border border-primary/30 bg-primary-soft p-5 shadow-e1">
            <h2 class="text-title font-semibold text-neutral-900">{{ __('recruitment.campaign.edit_schedule') }}</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div>
                    <x-forms.input-label for="campaign-edit-start" :value="__('recruitment.campaign.starts_at')" required />
                    <x-forms.input id="campaign-edit-start" type="datetime-local" model="editingStartsAt" :required="true" class="mt-1" />
                    @error('editingStartsAt') <p class="mt-1 text-small text-red-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <x-forms.input-label for="campaign-edit-end" :value="__('recruitment.campaign.ends_at')" required />
                    <x-forms.input id="campaign-edit-end" type="datetime-local" model="editingEndsAt" :required="true" class="mt-1" />
                    @error('editingEndsAt') <p class="mt-1 text-small text-red-700">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="mt-4 flex flex-wrap gap-3">
                <x-primary-button type="button" wire:click="updateSchedule" wire:target="updateSchedule">{{ __('general.save') }}</x-primary-button>
                <button type="button" wire:click="cancelScheduleEdit" class="cursor-pointer rounded-control border border-neutral-300 bg-white px-4 py-2 text-body font-semibold text-neutral-700 hover:bg-neutral-50">{{ __('general.cancel') }}</button>
            </div>
        </section>
    @endif

    @if ($disablingCampaignId)
        <section class="rounded-card border border-red-200 bg-red-50 p-5 shadow-e1">
            <h2 class="text-title font-semibold text-red-900">{{ __('recruitment.campaign.disable') }}</h2>
            <label for="campaign-disable-reason" class="mt-4 block text-body font-semibold text-neutral-800">{{ __('recruitment.campaign.disable_reason') }}</label>
            <textarea id="campaign-disable-reason" wire:model="disableReason" rows="3" class="mt-1 w-full rounded-control border border-neutral-300 bg-white px-3 py-2 text-body focus:border-primary focus:ring-primary"></textarea>
            @error('disableReason') <p class="mt-1 text-small text-red-700">{{ $message }}</p> @enderror
            <div class="mt-4 flex flex-wrap gap-3">
                <button type="button" wire:click="disableCampaign" wire:target="disableCampaign" class="cursor-pointer rounded-control bg-red-700 px-4 py-2 text-body font-semibold text-white hover:bg-red-800">{{ __('recruitment.campaign.confirm_disable') }}</button>
                <button type="button" wire:click="cancelDisable" class="cursor-pointer rounded-control border border-neutral-300 bg-white px-4 py-2 text-body font-semibold text-neutral-700 hover:bg-neutral-50">{{ __('general.cancel') }}</button>
            </div>
        </section>
    @endif

    <section class="overflow-hidden rounded-card border border-neutral-200 bg-white shadow-e1">
        <div class="border-b border-neutral-200 p-5">
            <h2 class="text-title font-semibold text-neutral-900">{{ __('recruitment.campaign.all') }}</h2>
        </div>

        @if ($campaigns->isEmpty())
            <p class="p-8 text-center text-body text-neutral-500">{{ __('recruitment.campaign.empty') }}</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-start text-body">
                    <thead class="bg-neutral-50 text-small font-semibold uppercase tracking-wide text-neutral-600">
                        <tr>
                            <th class="px-5 py-3 text-start">{{ __('recruitment.campaign.position') }}</th>
                            <th class="px-5 py-3 text-start">{{ __('recruitment.campaign.request') }}</th>
                            <th class="px-5 py-3 text-start">{{ __('recruitment.status') }}</th>
                            <th class="px-5 py-3 text-start">{{ __('recruitment.campaign.window') }}</th>
                            <th class="px-5 py-3 text-start">{{ __('recruitment.campaign.assets') }}</th>
                            <th class="px-5 py-3 text-start">{{ __('general.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @foreach ($campaigns as $campaign)
                            @php
                                $status = \App\Enums\HiringCampaignStatus::getLocalizedName((int) $campaign->status);
                                $jobReady = $campaign->jobPost?->currentVersion?->status === 'published';
                                $formReady = $campaign->applicationForm?->currentVersion?->status === 'published';
                            @endphp
                            <tr wire:key="campaign-{{ $campaign->id }}" class="align-top hover:bg-neutral-50/70">
                                <td class="px-5 py-4">
                                    <p class="font-semibold text-neutral-900">{{ $campaign->vacancy?->position?->title ?? $campaign->vacancy?->position?->title_en ?? '—' }}</p>
                                    <p class="mt-1 text-small text-neutral-500">{{ $campaign->vacancy?->position?->city?->name ?? '—' }} · {{ $campaign->vacancy?->position?->program?->name ?? '—' }}</p>
                                </td>
                                <td class="px-5 py-4 text-neutral-700">{{ $campaign->vacancy?->createdFromRequest?->request_number ?? '—' }}</td>
                                <td class="px-5 py-4"><span class="inline-flex rounded-full bg-neutral-100 px-3 py-1 text-small font-semibold text-neutral-700">{{ $status['value'] }}</span></td>
                                <td class="px-5 py-4 text-small text-neutral-600">
                                    <p>{{ $campaign->starts_at?->format('Y-m-d H:i') ?? '—' }}</p>
                                    <p class="mt-1">{{ $campaign->ends_at?->format('Y-m-d H:i') ?? '—' }}</p>
                                </td>
                                <td class="px-5 py-4 text-small">
                                    <p class="{{ $jobReady ? 'text-green-700' : 'text-neutral-500' }}">{{ __('recruitment.campaign.job_post') }}: {{ $jobReady ? __('recruitment.campaign.ready') : __('recruitment.campaign.not_ready') }}</p>
                                    <p class="mt-1 {{ $formReady ? 'text-green-700' : 'text-neutral-500' }}">{{ __('recruitment.campaign.application_form') }}: {{ $formReady ? __('recruitment.campaign.ready') : __('recruitment.campaign.not_ready') }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex max-w-md flex-wrap gap-2">
                                        <a href="{{ route('recruitment.campaigns.publishing', $campaign) }}" class="cursor-pointer rounded-control bg-primary px-3 py-2 text-small font-semibold text-white hover:bg-primary-hover">{{ __('recruitment.campaign.manage_publishing') }}</a>
                                        @can('manage', $campaign)
                                            @if (in_array((int) $campaign->status, $activeStatuses, true))
                                                <button type="button" wire:click="beginScheduleEdit({{ $campaign->id }})" class="cursor-pointer rounded-control border border-neutral-300 px-3 py-2 text-small font-semibold text-neutral-700 hover:bg-neutral-50">{{ __('recruitment.campaign.edit_schedule') }}</button>
                                            @endif
                                            @if ((int) $campaign->status === \App\Enums\HiringCampaignStatus::Published)
                                                <button type="button" wire:click="openCampaign({{ $campaign->id }})" wire:confirm="{{ __('recruitment.campaign.open_confirm') }}" class="cursor-pointer rounded-control border border-green-300 px-3 py-2 text-small font-semibold text-green-700 hover:bg-green-50">{{ __('recruitment.campaign.open') }}</button>
                                            @endif
                                            @if (in_array((int) $campaign->status, $activeStatuses, true))
                                                <button type="button" wire:click="closeCampaign({{ $campaign->id }})" wire:confirm="{{ __('recruitment.campaign.close_confirm') }}" class="cursor-pointer rounded-control border border-neutral-300 px-3 py-2 text-small font-semibold text-neutral-700 hover:bg-neutral-50">{{ __('recruitment.campaign.close') }}</button>
                                                <button type="button" wire:click="beginDisable({{ $campaign->id }})" class="cursor-pointer rounded-control border border-red-300 px-3 py-2 text-small font-semibold text-red-700 hover:bg-red-50">{{ __('recruitment.campaign.disable') }}</button>
                                            @endif
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-neutral-200 p-4">{{ $campaigns->links() }}</div>
        @endif
    </section>
</div>
