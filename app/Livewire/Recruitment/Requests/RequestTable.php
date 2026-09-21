<?php

namespace App\Livewire\Recruitment\Requests;

use App\Enums\RecruitmentRequestMode;
use App\Enums\RecruitmentRequestStatus;
use App\Enums\RecruitmentRequestType;
use App\Models\RecruitmentRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use RamonRietdijk\LivewireTables\Columns\Column;
use RamonRietdijk\LivewireTables\Livewire\LivewireTable;

class RequestTable extends LivewireTable
{
    public string $sortColumn = 'created_at';

    public string $sortDirection = 'desc';

    protected string $model = RecruitmentRequest::class;

    public function boot(): void
    {
        Gate::authorize('viewAny', RecruitmentRequest::class);
    }

    protected function query(): Builder
    {
        Gate::authorize('viewAny', RecruitmentRequest::class);

        return RecruitmentRequest::query()->visibleTo(auth()->user())
            ->with(['requester.profile', 'scopeCity', 'targetPosition']);
    }

    protected function link(Model $model): ?string
    {
        return route('recruitment.requests.show', ['requestId' => $model->getKey()]);
    }

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
}
