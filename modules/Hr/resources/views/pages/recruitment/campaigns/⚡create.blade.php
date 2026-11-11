<?php

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Modules\Hr\Actions\Recruitment\HiringCampaignService;
use Modules\Hr\Livewire\Concerns\Recruitment\AuthorizesRecruitment;
use Modules\Hr\Models\HiringCampaign;
use Modules\Hr\Models\Vacancy;

new #[Layout('layouts::desk'), Title('hr_recruitment.hub.titles.campaign_create')] class extends Component {
    use AuthorizesRecruitment;

    #[Locked]
    public ?int $sourceVacancyId = null;

    public ?string $vacancyId = null;

    public string $linkMode = HiringCampaign::PUBLIC_OPEN;

    public string $startsAt = '';

    public string $endsAt = '';

    public string $minimumAge = '';

    public string $maximumAge = '';

    public function mount(?int $vacancyId = null): void
    {
        $this->requirePermission('recruitment-campaigns.view', 'recruitment-campaigns.manage');
        Gate::authorize('viewAny', HiringCampaign::class);

        $this->sourceVacancyId = $vacancyId;
        $this->vacancyId = $vacancyId === null ? null : (string) $vacancyId;

        // With only one seat to choose from there is nothing to choose.
        if ($this->vacancyId === null) {
            $only = $this->availableVacancies()->take(2)->get(['vacancies.id']);
            $this->vacancyId = $only->count() === 1 ? (string) $only->first()->getKey() : null;
        }
    }

    public function save(HiringCampaignService $campaigns): void
    {
        $vacancy = $this->vacancyId === null ? null : $this->availableVacancies()->whereKey((int) $this->vacancyId)->first();

        if ($vacancy === null) {
            $this->addError('vacancyId', __('hr_recruitment.campaign_ui.choose_a_vacancy'));

            return;
        }

        try {
            $campaign = $campaigns->create($this->viewer(), $vacancy, [
                'link_mode' => $this->linkMode,
                'starts_at' => $this->startsAt,
                'ends_at' => $this->endsAt,
                'minimum_age' => $this->minimumAge,
                'maximum_age' => $this->maximumAge,
            ]);
        } catch (ValidationException $exception) {
            // The service owns every rule; this only shows what it said.
            foreach ($exception->errors() as $field => $messages) {
                $this->addError(\Illuminate\Support\Str::camel($field), $messages[0]);
            }

            return;
        }

        $this->toast(__('hr_recruitment.campaign_ui.created'));
        $this->redirectRoute('hr.recruitment.campaigns.show', ['campaignId' => $campaign->getKey()], navigate: true);
    }

    /** Only seats actually being recruited for, that this user may see. */
    #[Computed]
    public function vacancies()
    {
        return $this->availableVacancies()
            ->with([
                'position:id,title_en,title_ar,city_id,program_id',
                'position.city:id,name_en,name_ar',
                'position.program:id,name',
                'createdFromRequest:id,request_number',
            ])
            ->withCount('campaigns')
            ->get();
    }

    private function availableVacancies(): Builder
    {
        return Vacancy::query()->available()
            ->whereDoesntHave('campaigns', fn ($query) => $query->ongoing())
            ->visibleTo($this->viewer())
            ->when($this->sourceVacancyId !== null, fn ($query) => $query->whereKey($this->sourceVacancyId))
            ->orderByDesc('vacancies.id');
    }
};
?>

@php
    $muted = 'text-secondary-medium dark:text-ink-muted';
    $vacancies = $this->vacancies;
@endphp

<div class="mx-auto max-w-3xl space-y-6">
    <x-ui.detail-header
        :back="\App\Support\BackUrl::for(route('hr.recruitment.campaigns.index'))"
        :breadcrumbs="[['label' => __('hr_recruitment.hub.section')], ['label' => __('hr_recruitment.hub.titles.campaigns'), 'href' => route('hr.recruitment.campaigns.index')]]"
        :title="__('hr_recruitment.campaign_ui.new_campaign')"
        :subtitle="__('hr_recruitment.campaign_ui.create_next_hint')"
    />

    @if ($vacancies->isEmpty())
        <x-ui.panel>
            <x-ui.empty-state icon="briefcase" :heading="__('hr_recruitment.campaign_ui.no_open_vacancies')" />
        </x-ui.panel>
    @else
        <form wire:submit="save" novalidate class="space-y-6">
            <x-ui.panel :title="__('hr_recruitment.campaign_ui.vacancy')" :clip="false">
                <div class="space-y-2 p-4" role="radiogroup" aria-label="{{ __('hr_recruitment.campaign_ui.vacancy') }}">
                    @foreach ($vacancies as $vacancy)
                        <label class="block cursor-pointer" wire:key="vacancy-{{ $vacancy->id }}">
                            <input type="radio" class="peer sr-only" name="vacancy" value="{{ $vacancy->id }}" wire:model="vacancyId">
                            <span class="block rounded-md border border-panel-border p-3 transition peer-checked:border-primary peer-checked:bg-secondary-soft/60 peer-focus-visible:ring-2 peer-focus-visible:ring-primary dark:peer-checked:border-brand dark:peer-checked:bg-surface-2">
                                <span class="block font-semibold" dir="auto">{{ $vacancy->position?->title ?? '—' }}</span>
                                <span class="block text-sm {{ $muted }}">
                                    {{ collect([$vacancy->position?->program?->name, $vacancy->position?->city?->name, $vacancy->createdFromRequest?->request_number])->filter()->implode(' · ') }}
                                    · {{ __('hr_recruitment.remaining_slots', ['count' => $vacancy->needed_count - $vacancy->filled_count]) }}
                                    @if ($vacancy->campaigns_count > 0) · {{ __('hr_recruitment.campaign_ui.campaigns') }}: {{ $vacancy->campaigns_count }}@endif
                                </span>
                            </span>
                        </label>
                    @endforeach
                    @error('vacancyId')<p class="text-sm text-status-critical">{{ $message }}</p>@enderror
                    @error('vacancy')<p class="text-sm text-status-critical">{{ $message }}</p>@enderror
                </div>
            </x-ui.panel>

            <x-ui.panel :clip="false">
                <div class="grid gap-5 p-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <x-ui.form-select wire:model="linkMode" :label="__('hr_recruitment.campaign_ui.link_mode')" :help="__('hr_recruitment.campaign_ui.link_mode_hint')" required>
                            @foreach (HiringCampaign::LINK_MODES as $mode)
                                <option value="{{ $mode }}">{{ __('hr_recruitment.campaign_ui.modes.'.$mode) }}</option>
                            @endforeach
                        </x-ui.form-select>
                    </div>
                    <x-ui.form-input wire:model="startsAt" :label="__('hr_recruitment.campaign_ui.starts_at')" type="datetime-local" />
                    <x-ui.form-input wire:model="endsAt" :label="__('hr_recruitment.campaign_ui.ends_at')" type="datetime-local" :help="__('hr_recruitment.campaign_ui.window_hint')" />
                    <x-ui.form-input wire:model="minimumAge" :label="__('hr_recruitment.campaign_ui.minimum_age')" type="number" min="15" max="100" inputmode="numeric" />
                    <x-ui.form-input wire:model="maximumAge" :label="__('hr_recruitment.campaign_ui.maximum_age')" type="number" min="15" max="100" inputmode="numeric" :help="__('hr_recruitment.campaign_ui.age_range_hint')" />
                </div>
            </x-ui.panel>

            <div class="flex flex-wrap justify-end gap-2">
                <x-ui.button tone="secondary" :href="route('hr.recruitment.campaigns.index')" wire:navigate>{{ __('hr_recruitment.campaign_ui.cancel') }}</x-ui.button>
                <x-ui.button type="submit" tone="primary" icon="plus" wire:loading.attr="disabled" wire:target="save">{{ __('hr_recruitment.campaign_ui.create') }}</x-ui.button>
            </div>
        </form>
    @endif
</div>
