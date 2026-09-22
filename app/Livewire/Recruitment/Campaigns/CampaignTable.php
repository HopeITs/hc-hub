<?php

namespace App\Livewire\Recruitment\Campaigns;

use App\Enums\HiringCampaignStatus;
use App\Models\HiringCampaign;
use App\Services\HiringCampaignStateMachine;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use RamonRietdijk\LivewireTables\Columns\Column;
use RamonRietdijk\LivewireTables\Livewire\LivewireTable;

class CampaignTable extends LivewireTable
{
    public string $sortColumn = 'created_at';

    public string $sortDirection = 'desc';

    protected string $model = HiringCampaign::class;

    public function boot(): void
    {
        Gate::authorize('viewAny', HiringCampaign::class);
    }

    protected function query(): Builder
    {
        Gate::authorize('viewAny', HiringCampaign::class);

        return HiringCampaign::query()->visibleTo(auth()->user())
            ->with(['vacancy.position', 'creator.profile']);
    }

    protected function link(Model $model): ?string
    {
        return route('recruitment.campaigns.show', ['campaignId' => $model->getKey()]);
    }

    /** @return array<int, Column> */
    protected function columns(): array
    {
        return [
            Column::make(__('recruitment-campaign.position'), 'id')
                ->displayUsing(fn ($value, HiringCampaign $campaign): string => $campaign->vacancy?->position?->title ?? '—'),

            Column::make(__('recruitment-campaign.link_mode'), 'link_mode')
                ->displayUsing(fn ($value): string => __('recruitment-campaign.modes.'.$value)),

            // The status as the clock sees it, not as the column reads: a
            // campaign whose window shut an hour ago is expired here too, and
            // a list that said otherwise would be quietly lying.
            Column::make(__('recruitment-campaign.status'), 'status')
                ->displayUsing(function ($value, HiringCampaign $campaign): string {
                    $effective = app(HiringCampaignStateMachine::class)->effective($campaign);

                    return HiringCampaignStatus::getLocalizedName($effective)['value'];
                }),

            Column::make(__('recruitment-campaign.starts_at'), 'starts_at')
                ->displayUsing(fn ($value): string => $value?->format('Y-m-d') ?? '—'),

            Column::make(__('recruitment-campaign.ends_at'), 'ends_at')
                ->displayUsing(fn ($value): string => $value?->format('Y-m-d') ?? '—'),

            Column::make(__('recruitment-campaign.created_by'), 'created_by_user_id')
                ->displayUsing(fn ($value, HiringCampaign $campaign): string => $campaign->creator?->profile?->name
                    ?? $campaign->creator?->name ?? '—'),
        ];
    }
}
