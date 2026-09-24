<?php

namespace App\Livewire\Recruitment\Requests;

use App\Enums\RecruitmentRequestMode;
use App\Enums\RecruitmentRequestStatus;
use App\Enums\RecruitmentRequestType;
use App\Models\City;
use App\Models\RecruitmentRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use RamonRietdijk\LivewireTables\Columns\Column;
use RamonRietdijk\LivewireTables\Filters\SelectFilter;
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
     * A filter for every column the table shows, bar the date.
     *
     * The date is left out on purpose: a dropdown of every timestamp would be
     * one entry per row, which sorts far better than it filters — and the
     * column is already sortable.
     *
     * The options for requester, city and position are drawn from the rows the
     * viewer can actually see rather than from the whole table, so the list
     * does not name people and places outside their scope.
     *
     * @return array<int, SelectFilter>
     */
    protected function filters(): array
    {
        return [
            SelectFilter::make(__('recruitment.type'), 'request_type')
                ->options($this->enumOptions(RecruitmentRequestType::class, 'request_type')),

            SelectFilter::make(__('recruitment.status'), 'status')
                ->options($this->enumOptions(RecruitmentRequestStatus::class, 'status')),

            SelectFilter::make(__('recruitment.mode'), 'mode')
                ->options($this->enumOptions(RecruitmentRequestMode::class, 'mode')),

            SelectFilter::make(__('recruitment.number'), 'request_number')
                ->options($this->visible()->orderBy('request_number')
                    ->pluck('request_number', 'request_number')->all()),

            SelectFilter::make(__('recruitment.requester'), 'requester_user_id')
                ->options(User::query()
                    ->whereIn('id', $this->visible()->distinct()->pluck('requester_user_id'))
                    ->with('profile')->get()
                    ->mapWithKeys(fn (User $user): array => [
                        $user->id => $user->profile?->name ?? $user->name ?? '—',
                    ])->all()),

            SelectFilter::make(__('recruitment.city'), 'scope_city_id')
                ->options(City::query()
                    ->whereIn('id', $this->visible()->whereNotNull('scope_city_id')->distinct()->pluck('scope_city_id'))
                    ->orderBy(transColumn('name'))->get()
                    ->mapWithKeys(fn (City $city): array => [$city->id => $city->name])->all()),

            SelectFilter::make(__('recruitment.position'), 'target_position_id')
                ->options($this->visible()
                    ->whereNotNull('target_position_id')
                    ->with('targetPosition')->get()
                    ->filter(fn (RecruitmentRequest $request): bool => $request->targetPosition !== null)
                    ->mapWithKeys(fn (RecruitmentRequest $request): array => [
                        $request->target_position_id => $request->targetPosition->title,
                    ])->all()),
        ];
    }

    /** @return array<int, Column> */
    protected function columns(): array
    {
        return [
            Column::make(__('recruitment.number'), 'request_number')->sortable()->searchable(),
            Column::make(__('recruitment.type'), 'request_type')
                ->displayUsing(fn ($value) => RecruitmentRequestType::getLocalizedName((int) $value)['value'])->sortable(),
            Column::make(__('recruitment.status'), 'status')
                ->displayUsing(fn ($value) => RecruitmentRequestStatus::getLocalizedName((int) $value)['value'])->sortable(),
            Column::make(__('recruitment.mode'), 'mode')
                ->displayUsing(fn ($value) => RecruitmentRequestMode::getLocalizedName((int) $value)['value']),
            Column::make(__('recruitment.requester'), fn (Model $request) => $request->requester?->profile?->name ?? $request->requester?->name ?? '—'),
            Column::make(__('recruitment.city'), fn (Model $request) => $request->scopeCity?->name ?? '—'),
            Column::make(__('recruitment.position'), fn (Model $request) => $request->targetPosition?->title ?? '—'),
            Column::make(__('recruitment.created_at'), 'created_at')
                ->displayUsing(fn ($value) => $value?->format('Y-m-d H:i'))->sortable(),
        ];
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
}
