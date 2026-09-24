<?php

namespace App\Livewire\Recruitment\Requests;

use App\Enums\RecruitmentRequestMode;
use App\Enums\RecruitmentRequestStatus;
use App\Enums\RecruitmentRequestType;
use App\Models\City;
use App\Models\RecruitmentRequest;
use App\Models\User;
use App\Models\Vacancy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use RamonRietdijk\LivewireTables\Columns\Column;
use RamonRietdijk\LivewireTables\Columns\SelectColumn;
use RamonRietdijk\LivewireTables\Livewire\LivewireTable;

class RequestTable extends LivewireTable
{
    public string $sortColumn = 'created_at';

    public string $sortDirection = 'desc';

    /**
     * The status tab chosen above the table.
     *
     * A filter like any other, applied to the same scoped query. It arrives
     * from the page rather than being chosen here so the counts beside each
     * tab and the rows under it cannot disagree.
     */
    public string $statusFilter = 'all';

    protected string $model = RecruitmentRequest::class;

    public function boot(): void
    {
        Gate::authorize('viewAny', RecruitmentRequest::class);
    }

    protected function query(): Builder
    {
        Gate::authorize('viewAny', RecruitmentRequest::class);

        $query = RecruitmentRequest::query()->visibleTo(auth()->user())
            ->with(['requester.profile', 'scopeCity', 'targetPosition']);

        if ($this->statusFilter !== 'all') {
            $query->where('status', (int) $this->statusFilter);
        }

        return $query;
    }

    protected function link(Model $model): ?string
    {
        return route('recruitment.requests.show', ['requestId' => $model->getKey()]);
    }

    /**
     * The columns, each carrying its own control under its own heading.
     *
     * The package renders a second header row with a control for every column
     * that is searchable, which is where a filter belongs: under the name of
     * the thing it filters. A separate panel makes people hunt for the filter
     * and then remember which column it applied to.
     *
     * The date is deliberately left unsearchable. A dropdown of every
     * timestamp is one entry per row, which sorts well and filters not at all,
     * and the column is already sortable.
     *
     * Options come from the rows this viewer can see rather than from the
     * whole table, so the list never names a person or a place outside their
     * scope.
     *
     * @return array<int, Column>
     */
    protected function columns(): array
    {
        return [
            Column::make(__('recruitment.number'), 'request_number')->sortable()->searchable(),

            SelectColumn::make(__('recruitment.type'), 'request_type')
                ->options($this->enumOptions(RecruitmentRequestType::class, 'request_type'))
                ->displayUsing(fn ($value) => RecruitmentRequestType::getLocalizedName((int) $value)['value'])
                ->sortable()->searchable(),

            SelectColumn::make(__('recruitment.status'), 'status')
                ->options($this->enumOptions(RecruitmentRequestStatus::class, 'status'))
                ->displayUsing(fn ($value) => RecruitmentRequestStatus::getLocalizedName((int) $value)['value'])
                ->sortable()->searchable(),

            SelectColumn::make(__('recruitment.mode'), 'mode')
                ->options($this->enumOptions(RecruitmentRequestMode::class, 'mode'))
                ->displayUsing(fn ($value) => RecruitmentRequestMode::getLocalizedName((int) $value)['value'])
                ->searchable(),

            SelectColumn::make(__('recruitment.requester'), 'requester_user_id')
                ->options($this->requesterOptions())
                ->displayUsing(fn ($value, Model $request) => $request->requester?->profile?->name ?? $request->requester?->name ?? '—')
                ->searchable(),

            SelectColumn::make(__('recruitment.city'), 'scope_city_id')
                ->options($this->cityOptions())
                ->displayUsing(fn ($value, Model $request) => $request->scopeCity?->name ?? '—')
                ->searchable(),

            SelectColumn::make(__('recruitment.position'), 'target_position_id')
                ->options($this->positionOptions())
                ->displayUsing(fn ($value, Model $request) => $request->targetPosition?->title ?? '—')
                ->searchable(),

            Column::make(__('recruitment.created_at'), 'created_at')
                ->displayUsing(fn ($value) => $value?->format('Y-m-d H:i'))->sortable(),

            // An approved request has a seat waiting to be advertised, and the
            // person who advertises it is HR. Handing the vacancy over here
            // saves them finding it again in a list of every open seat.
            //
            // The column is hidden outright from anybody who cannot run a
            // campaign, and each row only offers the link when the request has
            // actually produced a seat to advertise.
            Column::make(__('recruitment-campaign.new_campaign'), 'id')
                ->displayUsing(function ($value, Model $request): string {
                    $vacancyId = $this->vacancyToAdvertise($request);

                    if ($vacancyId === null) {
                        return '';
                    }

                    return Blade::render(
                        '<a href="{{ $url }}" wire:navigate '
                        .'class="inline-block rounded-control bg-primary px-3 py-1.5 '
                        .'text-sm font-semibold text-white hover:bg-primary-600">{{ $label }}</a>',
                        [
                            'url' => route('recruitment.campaigns.create', ['vacancyId' => $vacancyId]),
                            'label' => __('recruitment-campaign.new_campaign'),
                        ],
                    );
                })
                ->asHtml()
                ->clickable(false)
                ->visible($this->mayRunCampaigns()),
        ];
    }

    /** @return array<int, string> */
    private function requesterOptions(): array
    {
        return User::query()
            ->whereIn('id', $this->visible()->distinct()->pluck('requester_user_id'))
            ->with('profile')->get()
            ->mapWithKeys(fn (User $user): array => [$user->id => $user->profile?->name ?? $user->name ?? '—'])
            ->all();
    }

    /** @return array<int, string> */
    private function cityOptions(): array
    {
        return City::query()
            ->whereIn('id', $this->visible()->whereNotNull('scope_city_id')->distinct()->pluck('scope_city_id'))
            ->orderBy(transColumn('name'))->get()
            ->mapWithKeys(fn (City $city): array => [$city->id => $city->name])
            ->all();
    }

    /** @return array<int, string> */
    private function positionOptions(): array
    {
        return $this->visible()
            ->whereNotNull('target_position_id')
            ->with('targetPosition')->get()
            ->filter(fn (RecruitmentRequest $request): bool => $request->targetPosition !== null)
            ->mapWithKeys(fn (RecruitmentRequest $request): array => [
                $request->target_position_id => $request->targetPosition->title,
            ])
            ->all();
    }

    /**
     * Every value of an enum that this viewer's rows actually use.
     *
     * Offering a filter that can only ever return nothing is worse than not
     * offering it, so the options come from the data rather than the enum.
     *
     * @param  class-string  $enum
     * @return array<int, string>
     */
    private function enumOptions(string $enum, string $column): array
    {
        return $this->visible()
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->mapWithKeys(fn ($value): array => [
                (int) $value => $enum::getLocalizedName((int) $value)['value'],
            ])
            ->all();
    }

    /** The rows this viewer may see, before any filter narrows them. */
    private function visible(): Builder
    {
        return RecruitmentRequest::query()->visibleTo(auth()->user());
    }

    /** May this user create campaigns at all? */
    private function mayRunCampaigns(): bool
    {
        return (bool) auth()->user()?->hasActivePermission('recruitment-campaigns.manage');
    }

    /**
     * The seat this request produced, if it is still there to advertise.
     *
     * Only a decided request has one: before that nothing has been applied and
     * there is no vacancy yet. A seat already filled or closed is not offered,
     * because a campaign cannot be opened on it and the button would only lead
     * to a refusal.
     */
    private function vacancyToAdvertise(Model $request): ?int
    {
        if (! in_array((int) $request->status, [RecruitmentRequestStatus::Approved, RecruitmentRequestStatus::Applied], true)) {
            return null;
        }

        return Vacancy::query()->available()
            ->where('created_from_request_id', $request->getKey())
            ->value('id')
            ?? Vacancy::query()->available()
                ->where('position_id', $request->target_position_id)
                ->value('id');
    }
}
