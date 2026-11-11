<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Modules\Hr\Actions\Recruitment\HiringCampaignService;
use Modules\Hr\Actions\Recruitment\PublicRecruitmentLinkService;
use Modules\Hr\Livewire\Concerns\Recruitment\AuthorizesRecruitment;
use Modules\Hr\Models\HiringCampaign;
use Modules\Hr\Models\PublicRecruitmentLink;

/*
 * The campaign's shareable address. Who may apply is the campaign's own
 * setting; the link follows it. Only a hash of the token is stored, so the
 * address is kept encrypted (session, then the row) for copying again —
 * regenerating would break every copy already shared.
 */
new #[Layout('layouts::desk'), Title('hr_recruitment.public_link.title')] class extends Component {
    use AuthorizesRecruitment;

    #[Locked]
    public int $campaignId;

    public bool $customExpiry = false;

    public string $expiresAt = '';

    public string $generatedUrl = '';

    public bool $confirmRegenerate = false;

    public bool $confirmDisable = false;

    public function mount(int $campaignId): void
    {
        $this->requirePermission('recruitment-links.view', 'recruitment-links.manage');
        $this->campaignId = $campaignId;
        $this->resetExpiry();
    }

    #[Computed]
    public function campaign(): HiringCampaign
    {
        $campaign = HiringCampaign::query()->visibleTo($this->viewer())
            ->with(['vacancy:id,position_id', 'vacancy.position:id,title_en,title_ar', 'jobPost.currentVersion', 'applicationForm.currentVersion'])
            ->findOrFail($this->campaignId);
        Gate::authorize('view', $campaign);

        return $campaign;
    }

    #[Computed]
    public function activeLink(): ?PublicRecruitmentLink
    {
        return $this->campaign->publicLinks()
            ->where('status', PublicRecruitmentLink::STATUS_ACTIVE)
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();
    }

    public function generate(PublicRecruitmentLinkService $service): void
    {
        $this->confirmRegenerate = false;
        Gate::authorize('create', [PublicRecruitmentLink::class, $this->campaign]);

        if ($this->customExpiry) {
            $this->validate(['expiresAt' => ['required', 'date', 'after_or_equal:today']]);
        }

        try {
            $result = $service->generate($this->campaign->fresh(), $this->viewer(), $this->customExpiry ? $this->expiresAt : null);
        } catch (DomainException $exception) {
            $this->toast($exception->getMessage(), false);

            return;
        }

        $this->generatedUrl = $result['url'];
        session()->put($this->sessionKey($result['link']), Crypt::encryptString($result['url']));
        $this->forget();
        $this->toast(__('hr_recruitment.public_link.generated'));
    }

    public function refreshVersions(PublicRecruitmentLinkService $service): void
    {
        $link = $this->activeLink;
        abort_if($link === null, 404);
        Gate::authorize('update', $link);

        try {
            $service->refreshVersions($link, $this->viewer());
        } catch (DomainException $exception) {
            $this->toast($exception->getMessage(), false);

            return;
        }

        $this->forget();
        $this->toast(__('hr_recruitment.public_link.refreshed'));
    }

    public function disable(PublicRecruitmentLinkService $service): void
    {
        $this->confirmDisable = false;
        $link = $this->activeLink;
        abort_if($link === null, 404);
        Gate::authorize('update', $link);
        $service->disable($link, $this->viewer());
        session()->forget($this->sessionKey($link));
        $this->generatedUrl = '';
        $this->forget();
        $this->toast(__('hr_recruitment.public_link.disabled'));
    }

    public function useCampaignExpiry(): void
    {
        $this->customExpiry = false;
        $this->resetValidation('expiresAt');
        $this->resetExpiry();
    }

    public function shareUrl(PublicRecruitmentLink $link): string
    {
        if ($this->generatedUrl !== '') {
            return $this->generatedUrl;
        }

        foreach ([session()->get($this->sessionKey($link)), $link->url_encrypted] as $stored) {
            if (is_string($stored) && $stored !== '') {
                try {
                    return Crypt::decryptString($stored);
                } catch (DecryptException) {
                    // try the next copy
                }
            }
        }

        return '';
    }

    private function forget(): void
    {
        unset($this->campaign, $this->activeLink);
    }

    private function resetExpiry(): void
    {
        $campaign = HiringCampaign::query()->visibleTo($this->viewer())->select(['id', 'ends_at'])->findOrFail($this->campaignId);
        $this->expiresAt = ($campaign->ends_at?->isFuture() ? $campaign->ends_at : now()->addDays(30))->format('Y-m-d');
    }

    private function sessionKey(PublicRecruitmentLink $link): string
    {
        return 'recruitment.public_link_url.'.$link->getKey();
    }
};
?>

@php
    $campaign = $this->campaign;
    $activeLink = $this->activeLink;
    $muted = 'text-secondary-medium dark:text-ink-muted';
    $fmt = fn ($date) => $date?->translatedFormat(__('formats.date'));
    $accepts = app(HiringCampaignService::class)->acceptsSubmissions($campaign);
    $current = $activeLink === null || app(PublicRecruitmentLinkService::class)->isCurrent($activeLink, $campaign);
    $mayManage = $activeLink ? Gate::allows('update', $activeLink) : Gate::allows('create', [PublicRecruitmentLink::class, $campaign]);
    $shareUrl = $activeLink ? $this->shareUrl($activeLink) : '';
@endphp

<div class="space-y-6">
    <x-ui.detail-header :back="route('hr.recruitment.campaigns.show', ['campaignId' => $campaign->id])" :title="__('hr_recruitment.public_link.title')" :subtitle="$campaign->vacancy?->position?->title"
        :breadcrumbs="[['label' => __('hr_recruitment.hub.titles.campaigns'), 'url' => route('hr.recruitment.campaigns.index')], ['label' => $campaign->vacancy?->position?->title ?? '#'.$campaign->id, 'url' => route('hr.recruitment.campaigns.show', ['campaignId' => $campaign->id])], ['label' => __('hr_recruitment.public_link.title')]]">
        @if ($campaign->isInvitationOnly())
            <x-slot:actions>
                <x-ui.button tone="secondary" icon="envelope" :href="route('hr.recruitment.campaigns.invites', ['campaignId' => $campaign->id])" wire:navigate>{{ __('hr_recruitment.public_link.invites') }}</x-ui.button>
            </x-slot:actions>
        @endif
    </x-ui.detail-header>

    <x-ui.panel :title="__('hr_recruitment.public_link.title')" :subtitle="__('hr_recruitment.public_link.help')" :clip="false">
        <div class="space-y-4 p-4">
            @if ($activeLink)
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <p class="font-semibold">{{ __($accepts ? 'hr_recruitment.public_link.active_until' : 'hr_recruitment.public_link.view_only_until', ['date' => $fmt($activeLink->expires_at)]) }}</p>
                    <flux:badge size="sm" :color="$accepts ? 'green' : 'zinc'">{{ __($accepts ? 'hr_recruitment.public_link.active' : 'hr_recruitment.public_link.view_only') }}</flux:badge>
                </div>
                @unless ($accepts)
                    <p class="text-sm {{ $muted }}">{{ __('hr_recruitment.public_link.view_only_notice') }}</p>
                @endunless

                @if ($shareUrl !== '')
                    <div class="flex flex-col gap-2 sm:flex-row" x-data="{ copied: false }">
                        <flux:input x-ref="url" readonly :value="$shareUrl" dir="ltr" class="flex-1" :aria-label="__('hr_recruitment.public_link.copy_now')" x-on:focus="$el.select()" />
                        <flux:button variant="primary" icon="clipboard" x-on:click="navigator.clipboard.writeText(@js($shareUrl)); copied = true; setTimeout(() => copied = false, 1800)">
                            <span x-show="!copied">{{ __('hr_recruitment.public_link.copy') }}</span>
                            <span x-cloak x-show="copied">{{ __('hr_recruitment.public_link.copied') }}</span>
                        </flux:button>
                        <x-ui.button tone="secondary" icon="arrow-top-right-on-square" :href="$shareUrl" target="_blank" rel="noopener">{{ __('hr_recruitment.public_link.open_page') }}</x-ui.button>
                    </div>
                @else
                    <p class="text-sm {{ $muted }}">{{ __('hr_recruitment.public_link.hash_only_notice') }}</p>
                @endif

                @if ($campaign->ends_at && $activeLink->expires_at?->lt($campaign->ends_at))
                    <p class="text-sm {{ $muted }}">{{ __('hr_recruitment.public_link.expires_early', ['date' => $fmt($campaign->ends_at)]) }}</p>
                @endif

                @unless ($current)
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-md border border-panel-border p-3" role="status">
                        <p class="text-sm">{{ __('hr_recruitment.public_link.stale') }}</p>
                        @if ($mayManage)
                            <x-ui.button tone="primary" wire:click="refreshVersions" wire:loading.attr="disabled" wire:target="refreshVersions">{{ __('hr_recruitment.public_link.refresh') }}</x-ui.button>
                        @endif
                    </div>
                @endunless
            @else
                <p class="text-sm {{ $muted }}">{{ __('hr_recruitment.public_link.will_expire', ['date' => $fmt($campaign->ends_at) ?? $fmt(now()->addDays(30))]) }}</p>
            @endif

            @if ($mayManage)
                <div class="space-y-3 border-t border-panel-border pt-4">
                    @if ($customExpiry)
                        <div class="max-w-sm space-y-1">
                            <x-ui.form-input type="date" wire:model="expiresAt" :label="__('hr_recruitment.public_link.expires_at')" />
                            <x-ui.button size="sm" tone="ghost" wire:click="useCampaignExpiry">{{ __('hr_recruitment.public_link.use_campaign_expiry') }}</x-ui.button>
                        </div>
                    @endif
                    <div class="flex flex-wrap items-center gap-2">
                        @if ($activeLink)
                            <x-ui.button tone="secondary" wire:click="$set('confirmRegenerate', true)">{{ __('hr_recruitment.public_link.regenerate') }}</x-ui.button>
                            <flux:button variant="danger" wire:click="$set('confirmDisable', true)">{{ __('hr_recruitment.public_link.disable') }}</flux:button>
                        @else
                            <x-ui.button tone="primary" icon="link" wire:click="generate" wire:loading.attr="disabled" wire:target="generate">{{ __('hr_recruitment.public_link.generate') }}</x-ui.button>
                        @endif
                        @unless ($customExpiry)
                            <x-ui.button size="sm" tone="ghost" wire:click="$set('customExpiry', true)">{{ __('hr_recruitment.public_link.custom_expiry') }}</x-ui.button>
                        @endunless
                    </div>
                </div>
            @endif
        </div>
    </x-ui.panel>

    <x-ui.confirm-modal wire:model="confirmRegenerate" tone="neutral" :title="__('hr_recruitment.public_link.regenerate')" :message="__('hr_recruitment.public_link.regenerate_confirm')" confirm="generate" :confirm-label="__('hr_recruitment.public_link.regenerate')" />
    <x-ui.confirm-modal wire:model="confirmDisable" :title="__('hr_recruitment.public_link.disable')" :message="__('hr_recruitment.public_link.disable_confirm')" confirm="disable" :confirm-label="__('hr_recruitment.public_link.disable')" />
</div>
