<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Modules\Hr\Actions\Recruitment\HiringCampaignService;
use Modules\Hr\Actions\Recruitment\HiringCampaignStateMachine;
use Modules\Hr\Actions\Recruitment\PublicRecruitmentLinkService;
use Modules\Hr\Enums\HiringCampaignStatus as Status;
use Modules\Hr\Livewire\Concerns\Recruitment\AuthorizesRecruitment;
use Modules\Hr\Models\ApplicationForm;
use Modules\Hr\Models\HiringCampaign;
use Modules\Hr\Models\JobPost;
use Modules\Hr\Models\PublicRecruitmentLink;
use Modules\Hr\Support\Recruitment\Labels;

/*
 * A campaign and its publishing checklist: details, job post, application
 * form and public link, then launch. Lifecycle actions (open, close,
 * reopen, disable, reactivate) are the state machine's; the page only offers
 * the ones it allows.
 */
new #[Layout('layouts::desk'), Title('hr_recruitment.hub.titles.campaign')] class extends Component {
    use AuthorizesRecruitment;

    #[Locked]
    public int $campaignId;

    public string $reason = '';

    public string $startsAt = '';

    public string $endsAt = '';

    public string $linkMode = HiringCampaign::PUBLIC_OPEN;

    public string $minimumAge = '';

    public string $maximumAge = '';

    public bool $editingDetails = false;

    /** The lifecycle action waiting for confirmation ('' when none). */
    public string $confirming = '';

    public bool $showConfirm = false;

    public function mount(int $campaignId): void
    {
        $this->requirePermission('recruitment-campaigns.view');
        $this->campaignId = $campaignId;
        $this->fillDetails($this->campaign);
    }

    #[Computed]
    public function campaign(): HiringCampaign
    {
        $campaign = HiringCampaign::query()->visibleTo($this->viewer())
            ->with([
                'vacancy:id,position_id,created_from_request_id,needed_count,filled_count',
                'vacancy.position:id,title_en,title_ar',
                'vacancy.createdFromRequest:id,request_number',
                'creator:id,email,employee_id',
                'closedBy:id,email,employee_id',
                'jobPost.currentVersion',
                'applicationForm.currentVersion',
                'publicLinks',
            ])
            ->withCount('applications')
            ->findOrFail($this->campaignId);

        Gate::authorize('view', $campaign);

        return $campaign;
    }

    public function ask(string $action): void
    {
        $this->resetErrorBag();
        $this->reason = '';
        $this->confirming = in_array($action, ['launch', 'open', 'close', 'reopen', 'disable', 'reactivate'], true) ? $action : '';
        $this->showConfirm = $this->confirming !== '';
    }

    public function confirmAction(HiringCampaignService $campaigns): void
    {
        $action = $this->confirming;

        if ($action === 'launch') {
            $this->launch($campaigns);
        } else {
            $this->act($action, $campaigns);
        }
    }

    public function launch(HiringCampaignService $campaigns): void
    {
        $this->run(function () use ($campaigns): void {
            $campaign = $campaigns->launch($this->viewer(), $this->campaign);
            $this->toast((int) $campaign->status === Status::Open->value
                ? __('hr_recruitment.campaign_ui.launched_open')
                : __('hr_recruitment.campaign_ui.launched_scheduled', ['date' => $campaign->starts_at?->translatedFormat(__('formats.date'))]));
        });
    }

    public function act(string $action, HiringCampaignService $campaigns): void
    {
        $campaign = $this->campaign;
        Gate::authorize('manage', $campaign);
        $reason = trim($this->reason) ?: null;

        if (in_array($action, ['close', 'disable'], true) && $reason === null) {
            $this->addError('reason', __('hr_recruitment.campaign_ui.reason_required'));

            return;
        }

        $this->run(function () use ($action, $campaigns, $campaign, $reason): void {
            match ($action) {
                'publish' => $campaigns->publish($this->viewer(), $campaign),
                'open' => $campaigns->open($this->viewer(), $campaign),
                'close' => $campaigns->close($this->viewer(), $campaign, $reason),
                'reopen' => $campaigns->reopen($this->viewer(), $campaign),
                'disable' => $campaigns->disable($this->viewer(), $campaign, $reason),
                'reactivate' => $campaigns->reactivate($this->viewer(), $campaign),
                default => throw new InvalidArgumentException('Unknown campaign action.'),
            };
            $this->toast(__('hr_recruitment.campaign_ui.done_'.$action));
        });
    }

    public function editDetails(): void
    {
        $this->fillDetails($this->campaign);
        $this->resetErrorBag();
        $this->editingDetails = true;
    }

    public function cancelDetails(): void
    {
        $this->fillDetails($this->campaign);
        $this->resetErrorBag();
        $this->editingDetails = false;
    }

    public function saveDetails(HiringCampaignService $campaigns): void
    {
        $this->resetErrorBag();

        try {
            $campaign = $campaigns->setWindow($this->viewer(), $this->campaign, [
                'starts_at' => $this->startsAt,
                'ends_at' => $this->endsAt,
                'link_mode' => $this->linkMode,
                'minimum_age' => $this->minimumAge,
                'maximum_age' => $this->maximumAge,
            ]);
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                $this->addError(\Illuminate\Support\Str::camel($field), $messages[0]);
            }

            return;
        }

        unset($this->campaign, $this->state);
        $this->fillDetails($campaign);
        $this->editingDetails = false;
        $this->toast(__('hr_recruitment.campaign_ui.window_saved'));
    }

    /** @return array<string, mixed> what the page shows, read once per render */
    #[Computed]
    public function state(): array
    {
        $campaign = $this->campaign;
        $states = app(HiringCampaignStateMachine::class);
        $effective = $states->effective($campaign);
        $mayManage = Gate::allows('manage', $campaign);
        $mayPublish = Gate::allows('publish', $campaign);
        $jobPost = $campaign->jobPost;
        $form = $campaign->applicationForm;
        $postReady = $this->isPublished($jobPost);
        $formReady = $this->isPublished($form);
        $activeLink = $campaign->publicLinks
            ->filter(fn (PublicRecruitmentLink $link): bool => $link->status === PublicRecruitmentLink::STATUS_ACTIVE && $link->expires_at?->isFuture())
            ->sortByDesc('id')
            ->first();
        $linkReady = $activeLink !== null;
        $isDraft = $effective === Status::Draft->value;
        $startsInFuture = ! $states->hasStarted($campaign);
        $allReady = $postReady && $formReady && $linkReady;

        return [
            'effective' => $effective,
            'isDraft' => $isDraft,
            'startsInFuture' => $startsInFuture,
            'acceptsSubmissions' => app(HiringCampaignService::class)->acceptsSubmissions($campaign),
            'mayEdit' => Gate::allows('update', $campaign) && ! in_array($effective, [Status::Expired->value, Status::Disabled->value], true),
            'postReady' => $postReady,
            'formReady' => $formReady,
            'linkReady' => $linkReady,
            'linkCurrent' => $activeLink === null || app(PublicRecruitmentLinkService::class)->isCurrent($activeLink, $campaign),
            'readyCount' => 1 + (int) $postReady + (int) $formReady + (int) $linkReady,
            'allReady' => $allReady,
            'canLaunch' => $mayPublish && $isDraft && ! $states->hasEnded($campaign),
            'canPublishOnly' => $mayPublish && $isDraft && $postReady && $formReady,
            'available' => $mayManage ? array_keys(array_filter([
                'open' => $effective === Status::Published->value && $states->can($campaign, 'open') && ! $states->hasEnded($campaign) && ! $startsInFuture && $allReady,
                'close' => $states->can($campaign, 'close') && $effective === Status::Open->value,
                'reopen' => $states->can($campaign, 'reopen') && ! $states->hasEnded($campaign),
                'disable' => $states->can($campaign, 'disable') && $effective !== Status::Disabled->value,
                'reactivate' => $effective === Status::Disabled->value,
            ])) : [],
            'canManageJobPost' => $this->may('recruitment-job-posts.manage') && ($jobPost ? Gate::allows('manage', $jobPost) : Gate::allows('create', [JobPost::class, $campaign])),
            'canManageForm' => $this->may('recruitment-forms.manage') && ($form ? Gate::allows('manage', $form) : Gate::allows('create', [ApplicationForm::class, $campaign])),
            'canSeeLinks' => $this->may('recruitment-links.view') || $this->may('recruitment-links.manage'),
            'canInvite' => $this->may('recruitment-applicants.manage') && $campaign->isInvitationOnly(),
        ];
    }

    private function run(callable $work): void
    {
        try {
            $work();
            $this->showConfirm = false;
            $this->confirming = '';
            $this->reason = '';
        } catch (ValidationException $exception) {
            $this->toast(collect($exception->errors())->flatten()->first() ?? __('hr_recruitment.campaign_ui.transition_not_allowed'), false);
        }

        unset($this->campaign, $this->state);
    }

    private function isPublished($asset): bool
    {
        return $asset?->status === 'published'
            && $asset?->currentVersion?->status === 'published'
            && $asset?->currentVersion?->published_at !== null;
    }

    private function fillDetails(HiringCampaign $campaign): void
    {
        $this->startsAt = $campaign->starts_at?->format('Y-m-d') ?? '';
        $this->endsAt = $campaign->ends_at?->format('Y-m-d') ?? '';
        $this->linkMode = $campaign->link_mode ?: HiringCampaign::PUBLIC_OPEN;
        $this->minimumAge = $campaign->minimum_age === null ? '' : (string) $campaign->minimum_age;
        $this->maximumAge = $campaign->maximum_age === null ? '' : (string) $campaign->maximum_age;
    }
};
?>

@php
    $campaign = $this->campaign;
    $state = $this->state;
    $effective = $state['effective'];
    $muted = 'text-secondary-medium dark:text-ink-muted';
    $fmt = fn ($date) => $date?->translatedFormat(__('formats.date'));
    $request = $campaign->vacancy?->createdFromRequest;
    $statusKey = match ($effective) {
        Status::Draft->value => 'draft',
        Status::Published->value => $state['startsInFuture'] ? 'scheduled' : 'published',
        Status::Open->value => $campaign->ends_at ? 'open' : 'open_no_end',
        Status::Closed->value => 'closed',
        Status::Expired->value => 'expired',
        default => 'disabled',
    };
    $steps = [
        ['key' => 'details', 'done' => true, 'url' => null, 'action' => null],
        ['key' => 'post', 'done' => $state['postReady'], 'url' => $state['canManageJobPost'] ? route('hr.recruitment.campaigns.job-post.edit', ['campaignId' => $campaign->id]) : null, 'action' => $campaign->jobPost ? 'open_post' : 'create_post'],
        ['key' => 'form', 'done' => $state['formReady'], 'url' => $state['canManageForm'] ? route('hr.recruitment.campaigns.application-form.edit', ['campaignId' => $campaign->id]) : null, 'action' => $campaign->applicationForm ? 'open_form' : 'create_form'],
        ['key' => 'link', 'done' => $state['linkReady'], 'url' => $state['canSeeLinks'] ? route('hr.recruitment.campaigns.links', ['campaignId' => $campaign->id]) : null, 'action' => null],
    ];
@endphp

<div class="space-y-6" data-campaign="{{ $campaign->id }}">
    <x-ui.detail-header
        :back="\App\Support\BackUrl::for(route('hr.recruitment.campaigns.index'))"
        :breadcrumbs="[['label' => __('hr_recruitment.hub.section')], ['label' => __('hr_recruitment.hub.titles.campaigns'), 'href' => route('hr.recruitment.campaigns.index')]]"
        :title="$campaign->vacancy?->position?->title ?? __('hr_recruitment.campaign_ui.page.campaign_number', ['id' => $campaign->id])"
    >
        <x-slot:meta>
            <x-ui.enum-badge :case="Status::from($effective)" :label="Labels::enum('HiringCampaignStatus', $effective)" :tone="Labels::campaignTone($effective)" />
            <span>{{ __('hr_recruitment.campaign_ui.modes.'.$campaign->link_mode) }}</span>
            @if ($request)
                <a href="{{ route('hr.recruitment.requests.show', ['requestId' => $request->id]) }}" wire:navigate class="tabular-nums underline-offset-4 hover:underline"><bdi>{{ $request->request_number }}</bdi></a>
            @endif
            <span>{{ __('hr_recruitment.campaign_ui.page.applications') }}: <span class="tabular-nums">{{ $campaign->applications_count }}</span></span>
        </x-slot:meta>
        <x-slot:actions>
            @if ($campaign->applications_count > 0 && Route::has('hr.recruitment.applicants.index') && $this->may('recruitment-applicants.view'))
                <x-ui.button tone="secondary" icon="users" :href="route('hr.recruitment.applicants.index', ['campaign' => $campaign->id])" wire:navigate>{{ __('hr_recruitment.hub.titles.applicants') }}</x-ui.button>
            @endif
            @if ($state['canInvite'])
                <x-ui.button tone="secondary" icon="envelope" :href="route('hr.recruitment.campaigns.invites', ['campaignId' => $campaign->id])" wire:navigate>{{ __('hr_recruitment.hub.titles.invites') }}</x-ui.button>
            @endif
            @foreach ($state['available'] as $action)
                <x-ui.button :tone="in_array($action, ['open', 'reopen', 'reactivate'], true) ? 'primary' : 'secondary'" wire:click="ask('{{ $action }}')">{{ __('hr_recruitment.campaign_ui.act.'.$action) }}</x-ui.button>
            @endforeach
        </x-slot:actions>
    </x-ui.detail-header>

    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_20rem]">
        <div class="min-w-0 space-y-6">
            {{-- Details: the window, the audience and the age range. --}}
            <x-ui.panel :title="__('hr_recruitment.campaign_ui.page.step_details')" :subtitle="__('hr_recruitment.campaign_ui.page.step_details_help')" :clip="false">
                @if ($state['mayEdit'])
                    <x-slot:toolbar>
                        @unless ($editingDetails)
                            <x-ui.button size="sm" tone="secondary" icon="pencil-square" wire:click="editDetails">{{ __('hr_recruitment.campaign_ui.page.edit') }}</x-ui.button>
                        @endunless
                    </x-slot:toolbar>
                @endif
                @if ($editingDetails)
                    <form wire:submit="saveDetails" class="grid gap-5 p-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            @if ($state['isDraft'])
                                <x-ui.form-select wire:model="linkMode" :label="__('hr_recruitment.campaign_ui.link_mode')" required>
                                    @foreach (HiringCampaign::LINK_MODES as $mode)
                                        <option value="{{ $mode }}">{{ __('hr_recruitment.campaign_ui.modes.'.$mode) }}</option>
                                    @endforeach
                                </x-ui.form-select>
                            @else
                                <p class="text-sm font-semibold">{{ __('hr_recruitment.campaign_ui.link_mode') }}</p>
                                <p class="text-sm">{{ __('hr_recruitment.campaign_ui.modes.'.$campaign->link_mode) }}</p>
                                <p class="text-xs {{ $muted }}">{{ __('hr_recruitment.campaign_ui.page.audience_locked_hint') }}</p>
                            @endif
                        </div>
                        <x-ui.form-input wire:model="startsAt" :label="__('hr_recruitment.campaign_ui.starts_at')" type="date" />
                        <x-ui.form-input wire:model="endsAt" :label="__('hr_recruitment.campaign_ui.ends_at')" type="date" :help="__('hr_recruitment.campaign_ui.window_hint')" />
                        <x-ui.form-input wire:model="minimumAge" :label="__('hr_recruitment.campaign_ui.minimum_age')" type="number" min="15" max="100" inputmode="numeric" />
                        <x-ui.form-input wire:model="maximumAge" :label="__('hr_recruitment.campaign_ui.maximum_age')" type="number" min="15" max="100" inputmode="numeric" :help="__('hr_recruitment.campaign_ui.age_range_hint')" />
                        <div class="flex justify-end gap-2 sm:col-span-2">
                            <x-ui.button tone="ghost" wire:click="cancelDetails">{{ __('hr_recruitment.campaign_ui.cancel') }}</x-ui.button>
                            <x-ui.button type="submit" tone="primary" wire:loading.attr="disabled" wire:target="saveDetails">{{ __('hr_recruitment.campaign_ui.page.save_details') }}</x-ui.button>
                        </div>
                    </form>
                @else
                    <dl class="grid gap-4 p-4 text-sm sm:grid-cols-2">
                        <div><dt class="{{ $muted }}">{{ __('hr_recruitment.campaign_ui.window') }}</dt>
                            <dd class="font-medium tabular-nums">{{ $fmt($campaign->starts_at) ?? __('hr_recruitment.campaign_ui.page.on_launch') }} → {{ $fmt($campaign->ends_at) ?? __('hr_recruitment.campaign_ui.page.no_deadline') }}</dd></div>
                        <div><dt class="{{ $muted }}">{{ __('hr_recruitment.campaign_ui.link_mode') }}</dt>
                            <dd class="font-medium">{{ __('hr_recruitment.campaign_ui.modes.'.$campaign->link_mode) }}</dd>
                            <dd class="text-xs {{ $muted }}">{{ __('hr_recruitment.campaign_ui.mode_hints.'.$campaign->link_mode) }}</dd></div>
                        <div><dt class="{{ $muted }}">{{ __('hr_recruitment.campaign_ui.age_range') }}</dt>
                            <dd class="font-medium tabular-nums">{{ $campaign->minimum_age || $campaign->maximum_age ? ($campaign->minimum_age ?? '…').' – '.($campaign->maximum_age ?? '…') : '—' }}</dd></div>
                        <div><dt class="{{ $muted }}">{{ __('hr_recruitment.campaign_ui.created_by') }}</dt>
                            <dd class="font-medium" dir="auto">{{ $campaign->creator?->name ?? '—' }} · {{ $fmt($campaign->created_at) }}</dd></div>
                        @if ($campaign->closedBy)
                            <div class="sm:col-span-2"><dt class="{{ $muted }}">{{ __('hr_recruitment.campaign_ui.closed_by') }}</dt>
                                <dd class="font-medium" dir="auto">{{ $campaign->closedBy->name }}@if (filled($campaign->closed_reason)) · {{ $campaign->closed_reason }}@endif</dd></div>
                        @endif
                    </dl>
                @endif
            </x-ui.panel>

            {{-- The publishing checklist. --}}
            <x-ui.panel :title="__('hr_recruitment.campaign_ui.page.checklist')" :subtitle="__('hr_recruitment.campaign_ui.page.ready_count', ['done' => $state['readyCount'], 'total' => 4])">
                <ol class="divide-y divide-panel-border">
                    @foreach ($steps as $step)
                        <li class="flex flex-wrap items-center justify-between gap-3 px-4 py-3" wire:key="step-{{ $step['key'] }}">
                            <div class="flex min-w-0 items-start gap-3">
                                <span @class(['mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full', 'bg-secondary-dark text-white dark:bg-ink dark:text-surface-0' => $step['done'], 'border-2 border-secondary-light dark:border-line' => ! $step['done']])>
                                    @if ($step['done'])<flux:icon.check variant="micro" class="size-3" />@endif
                                </span>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold">{{ __('hr_recruitment.campaign_ui.page.step_'.$step['key']) }}</p>
                                    <p class="text-xs {{ $muted }}">
                                        {{ $step['done'] ? __('hr_recruitment.campaign_ui.page.done') : __('hr_recruitment.campaign_ui.page.to_do') }}
                                        @if ($step['key'] === 'link' && ! $state['linkCurrent']) · {{ __('hr_recruitment.campaign_ui.page.link_waits') }}@endif
                                        @if ($step['key'] === 'link' && ! $state['canSeeLinks']) · {{ __('hr_recruitment.campaign_ui.page.link_no_permission') }}@endif
                                    </p>
                                </div>
                            </div>
                            @if ($step['url'])
                                <x-ui.button size="sm" tone="secondary" :href="$step['url']" wire:navigate>{{ $step['action'] ? __('hr_recruitment.campaign_ui.page.'.$step['action']) : __('hr_recruitment.hub.open') }}</x-ui.button>
                            @endif
                        </li>
                    @endforeach
                </ol>
                @if ($state['canLaunch'] || $state['canPublishOnly'])
                    <div class="flex flex-wrap items-center justify-end gap-2 border-t border-panel-border px-4 py-3">
                        @if (! $state['allReady'])
                            <p class="me-auto text-sm {{ $muted }}">{{ __('hr_recruitment.campaign_ui.page.launch_blocked') }}</p>
                        @endif
                        @if ($state['canPublishOnly'])
                            <x-ui.button tone="secondary" wire:click="act('publish')" wire:loading.attr="disabled" wire:target="act">{{ __('hr_recruitment.campaign_ui.page.publish_only') }}</x-ui.button>
                        @endif
                        @if ($state['canLaunch'])
                            <x-ui.button tone="primary" icon="rocket-launch" wire:click="ask('launch')" :disabled="! $state['allReady']">{{ $state['startsInFuture'] ? __('hr_recruitment.campaign_ui.page.launch_scheduled') : __('hr_recruitment.campaign_ui.page.launch') }}</x-ui.button>
                        @endif
                    </div>
                @endif
            </x-ui.panel>
        </div>

        <aside class="space-y-4 xl:sticky xl:top-24">
            <x-ui.panel :title="__('hr_recruitment.campaign_ui.page.status_title')">
                <div class="space-y-2 p-4 text-sm">
                    <p>{{ __('hr_recruitment.campaign_ui.page.status_'.$statusKey, ['start' => $fmt($campaign->starts_at), 'end' => $fmt($campaign->ends_at)]) }}</p>
                    @if ($state['startsInFuture'] && $campaign->starts_at)
                        <p class="{{ $muted }}">{{ __('hr_recruitment.campaign_ui.page.not_started') }} · {{ $fmt($campaign->starts_at) }}</p>
                    @endif
                    @if ($campaign->vacancy)
                        <p class="{{ $muted }}">{{ __('hr_recruitment.remaining_slots', ['count' => $campaign->vacancy->needed_count - $campaign->vacancy->filled_count]) }}</p>
                    @endif
                </div>
            </x-ui.panel>
        </aside>
    </div>

    <flux:modal wire:model="showConfirm" class="max-w-lg">
        @if ($confirming !== '')
            <div class="space-y-4">
                <flux:heading size="lg">{{ $confirming === 'launch' ? __('hr_recruitment.campaign_ui.page.launch') : __('hr_recruitment.campaign_ui.act.'.$confirming) }}</flux:heading>
                <p class="text-sm">
                    @if ($confirming === 'launch')
                        {{ $state['startsInFuture'] ? __('hr_recruitment.campaign_ui.page.launch_confirm_scheduled', ['date' => $fmt($campaign->starts_at)]) : __('hr_recruitment.campaign_ui.page.launch_confirm_now') }}
                    @else
                        {{ __('hr_recruitment.campaign_ui.confirm.'.$confirming) }}
                    @endif
                </p>
                @if (in_array($confirming, ['close', 'disable'], true))
                    <x-ui.form-textarea wire:model="reason" :label="__('hr_recruitment.campaign_ui.reason')" :help="__('hr_recruitment.campaign_ui.reason_hint')" rows="3" required dir="auto" />
                @endif
                <div class="flex justify-end gap-2">
                    <x-ui.button tone="ghost" wire:click="$set('showConfirm', false)">{{ __('hr_recruitment.campaign_ui.cancel') }}</x-ui.button>
                    <x-ui.button :tone="in_array($confirming, ['close', 'disable'], true) ? 'neutral' : 'primary'" wire:click="confirmAction" wire:loading.attr="disabled" wire:target="confirmAction">{{ $confirming === 'launch' ? __('hr_recruitment.campaign_ui.page.launch') : __('hr_recruitment.campaign_ui.act.'.$confirming) }}</x-ui.button>
                </div>
            </div>
        @endif
    </flux:modal>
</div>
