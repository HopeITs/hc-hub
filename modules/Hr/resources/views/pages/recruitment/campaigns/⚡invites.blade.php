<?php

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Hr\Actions\Recruitment\RecruitmentCampaignInviteService;
use Modules\Hr\Livewire\Concerns\Recruitment\AuthorizesRecruitment;
use Modules\Hr\Models\HiringCampaign;
use Modules\Hr\Models\RecruitmentCampaignInvite;

/*
 * The invitation list of an invitation-only campaign: add people, send each
 * of them the application link, cancel an invitation before it is used.
 */
new #[Layout('layouts::desk'), Title('hr_recruitment.hub.titles.invites')] class extends Component {
    use AuthorizesRecruitment;
    use WithPagination;

    public const STATUSES = [
        RecruitmentCampaignInvite::STATUS_PENDING,
        RecruitmentCampaignInvite::STATUS_SENT,
        RecruitmentCampaignInvite::STATUS_USED,
        RecruitmentCampaignInvite::STATUS_CANCELLED,
    ];

    #[Locked]
    public int $campaignId;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $status = '';

    public string $fullName = '';

    public string $email = '';

    #[Locked]
    public ?int $cancellingId = null;

    public bool $confirmCancel = false;

    public function mount(int $campaignId): void
    {
        $this->campaignId = $campaignId;
        $this->campaign;
    }

    #[Computed]
    public function campaign(): HiringCampaign
    {
        $campaign = HiringCampaign::query()->visibleTo($this->viewer())
            ->with(['vacancy:id,position_id', 'vacancy.position:id,title_en,title_ar'])
            ->findOrFail($this->campaignId);
        Gate::authorize('manageForCampaign', [RecruitmentCampaignInvite::class, $campaign]);

        return $campaign;
    }

    #[Computed]
    public function rows(): LengthAwarePaginator
    {
        $term = trim($this->search);

        return RecruitmentCampaignInvite::query()
            ->select(['id', 'campaign_id', 'full_name', 'email_normalized', 'status', 'failure_reason', 'used_at', 'created_at'])
            ->where('campaign_id', $this->campaignId)
            ->when(in_array($this->status, self::STATUSES, true), fn ($query) => $query->where('status', $this->status))
            ->when($term !== '', fn ($query) => $query->where(fn ($inner) => $inner->where('full_name', 'like', "%{$term}%")->orWhere('email_normalized', 'like', '%'.mb_strtolower($term).'%')))
            ->latest('id')
            ->paginate(25);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function add(RecruitmentCampaignInviteService $invites): void
    {
        $this->campaign;
        $this->validate([
            'fullName' => ['required', 'string', 'max:191'],
            'email' => ['required', 'email:rfc', 'max:191'],
        ]);

        $invites->add($this->viewer(), $this->campaign, $this->fullName, $this->email);
        $this->reset(['fullName', 'email']);
        unset($this->rows);
        $this->toast(__('hr_recruitment.invites_ui.invite_added'));
    }

    public function send(int $inviteId, RecruitmentCampaignInviteService $invites): void
    {
        try {
            $invites->send($this->viewer(), $this->invite($inviteId));
            $this->toast(__('hr_recruitment.invites_ui.invite_sent_toast'));
        } catch (ValidationException $exception) {
            $this->toast((string) collect($exception->errors())->collapse()->first(), false);
        }

        unset($this->rows);
    }

    public function sendPending(RecruitmentCampaignInviteService $invites): void
    {
        $this->campaign;

        try {
            ['sent' => $sent, 'failed' => $failed] = $invites->sendPending($this->viewer(), $this->campaign);
        } catch (ValidationException $exception) {
            $this->toast((string) collect($exception->errors())->collapse()->first(), false);

            return;
        }

        unset($this->rows);

        if ($sent === 0 && $failed === 0) {
            $this->toast(__('hr_recruitment.invites_ui.invites_none_pending'), false);

            return;
        }

        if ($sent > 0) {
            $this->toast(__('hr_recruitment.invites_ui.invites_sent_toast', ['count' => $sent]));
        }

        if ($failed > 0) {
            $this->toast(__('hr_recruitment.invites_ui.invites_failed_notice', ['count' => $failed]), false);
        }
    }

    public function askCancel(int $inviteId): void
    {
        $this->cancellingId = $this->invite($inviteId)->id;
        $this->confirmCancel = true;
    }

    public function cancel(RecruitmentCampaignInviteService $invites): void
    {
        $this->confirmCancel = false;

        if ($this->cancellingId === null) {
            return;
        }

        try {
            $invites->cancel($this->viewer(), $this->invite($this->cancellingId));
            $this->toast(__('hr_recruitment.invites_ui.invite_cancelled'));
        } catch (ValidationException $exception) {
            $this->toast((string) collect($exception->errors())->collapse()->first(), false);
        }

        $this->cancellingId = null;
        unset($this->rows);
    }

    /** Scoped to this campaign, so a tampered id cannot reach another campaign's invite. */
    private function invite(int $inviteId): RecruitmentCampaignInvite
    {
        $this->campaign;

        return RecruitmentCampaignInvite::query()->where('campaign_id', $this->campaignId)->findOrFail($inviteId);
    }
};
?>

@php
    $campaign = $this->campaign;
    $rows = $this->rows;
    $muted = 'text-secondary-medium dark:text-ink-muted';
@endphp

<div class="space-y-6">
    <x-ui.detail-header :back="route('hr.recruitment.campaigns.show', ['campaignId' => $campaign->id])" :title="__('hr_recruitment.invites_ui.invites_title')" :subtitle="__('hr_recruitment.invites_ui.invites_description')"
        :breadcrumbs="[['label' => __('hr_recruitment.hub.titles.campaigns'), 'url' => route('hr.recruitment.campaigns.index')], ['label' => $campaign->vacancy?->position?->title ?? '#'.$campaign->id, 'url' => route('hr.recruitment.campaigns.show', ['campaignId' => $campaign->id])], ['label' => __('hr_recruitment.invites_ui.invites_title')]]">
        <x-slot:actions>
            <x-ui.button tone="secondary" icon="paper-airplane" wire:click="sendPending" wire:loading.attr="disabled" wire:target="sendPending">{{ __('hr_recruitment.invites_ui.invite_send_pending') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.detail-header>

    @unless ($campaign->isInvitationOnly())
        <p class="text-sm {{ $muted }}" role="status">{{ __('hr_recruitment.invites_ui.invite_not_invitation_only') }}</p>
    @endunless

    <x-ui.panel :clip="false">
        <form wire:submit="add" class="grid gap-3 p-4 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] md:items-end">
            <x-ui.form-input wire:model="fullName" :label="__('hr_recruitment.invites_ui.invite_full_name')" required autocomplete="off" />
            <x-ui.form-input type="email" wire:model="email" :label="__('hr_recruitment.invites_ui.invite_email')" required dir="ltr" autocomplete="off" />
            <x-ui.button type="submit" tone="primary" icon="plus" wire:loading.attr="disabled" wire:target="add">{{ __('hr_recruitment.invites_ui.invite_add') }}</x-ui.button>
        </form>
    </x-ui.panel>

    <x-desk.register heading-id="recruitment-invites-heading" :title="__('hr_recruitment.invites_ui.invites_title')" :count="$rows->total()">
        <x-slot:search>
            <x-desk.register-search :label="__('hr_recruitment.hub.search')" :placeholder="__('hr_recruitment.invites_ui.invite_email')" wire:model.live.debounce.300ms="search" />
        </x-slot:search>
        <x-slot:filters>
            <x-ui.header-filter-select :label="__('hr_recruitment.invites_ui.invite_status')" wire:model.live="status" :value="$status">
                <option value="">{{ __('hr_recruitment.hub.all_statuses') }}</option>
                @foreach ($this::STATUSES as $value)
                    <option value="{{ $value }}">{{ __('hr_recruitment.invites_ui.invite_status_'.$value) }}</option>
                @endforeach
            </x-ui.header-filter-select>
        </x-slot:filters>

        @if ($rows->isEmpty())
            <x-ui.empty-state icon="envelope" :heading="$search === '' && $status === '' ? __('hr_recruitment.public_link.no_invites') : __('hr_recruitment.hub.no_matching')" />
        @else
            <x-ui.data-table :caption="__('hr_recruitment.invites_ui.invites_title')">
                <x-slot:head>
                    <th>{{ __('hr_recruitment.invites_ui.invite_full_name') }}</th>
                    <th>{{ __('hr_recruitment.invites_ui.invite_email') }}</th>
                    <th>{{ __('hr_recruitment.invites_ui.invite_status') }}</th>
                    <th>{{ __('hr_recruitment.invites_ui.invite_used_at') }}</th>
                    <th><span class="sr-only">{{ __('hr_recruitment.hub.actions') }}</span></th>
                </x-slot:head>
                @foreach ($rows as $invite)
                    @php
                        $failed = $invite->hasDeliveryFailed();
                        $open = ! in_array($invite->status, [RecruitmentCampaignInvite::STATUS_CANCELLED, RecruitmentCampaignInvite::STATUS_USED], true);
                    @endphp
                    <tr wire:key="invite-{{ $invite->id }}">
                        <x-ui.data-table.cell :label="__('hr_recruitment.invites_ui.invite_full_name')"><span dir="auto">{{ $invite->full_name }}</span></x-ui.data-table.cell>
                        <x-ui.data-table.cell :label="__('hr_recruitment.invites_ui.invite_email')"><bdi dir="ltr">{{ $invite->email_normalized }}</bdi></x-ui.data-table.cell>
                        <x-ui.data-table.cell :label="__('hr_recruitment.invites_ui.invite_status')">
                            {{ __('hr_recruitment.invites_ui.invite_status_'.$invite->status) }}
                            @if ($failed)
                                <span class="block text-xs text-status-critical">{{ __('hr_recruitment.invites_ui.invite_status_failed') }} — {{ __('hr_recruitment.invites_ui.invite_failure.'.$invite->failure_reason) }}</span>
                            @endif
                        </x-ui.data-table.cell>
                        <x-ui.data-table.cell :label="__('hr_recruitment.invites_ui.invite_used_at')"><span class="tabular-nums">{{ $invite->used_at?->translatedFormat(__('formats.date')) ?? '—' }}</span></x-ui.data-table.cell>
                        <td>
                            @if ($open)
                                <span class="flex flex-wrap items-center justify-end gap-1">
                                    <x-ui.button size="sm" tone="ghost" wire:click="send({{ $invite->id }})">{{ __($invite->status === RecruitmentCampaignInvite::STATUS_SENT || $failed ? 'hr_recruitment.invites_ui.invite_resend' : 'hr_recruitment.invites_ui.invite_send') }}</x-ui.button>
                                    <x-ui.button size="sm" tone="ghost" wire:click="askCancel({{ $invite->id }})">{{ __('hr_recruitment.invites_ui.invite_cancel') }}</x-ui.button>
                                </span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-ui.data-table>
            <div class="mt-4"><flux:pagination :paginator="$rows" scroll-to="#recruitment-invites-heading" /></div>
        @endif
    </x-desk.register>

    <x-ui.confirm-modal wire:model="confirmCancel" :title="__('hr_recruitment.invites_ui.invite_cancel')" :message="__('hr_recruitment.invites_ui.invite_cancel_confirm')" confirm="cancel" :confirm-label="__('hr_recruitment.invites_ui.invite_cancel')" />
</div>
