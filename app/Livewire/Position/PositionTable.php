<?php

namespace App\Livewire\Position;

use App\Models\Center;
use App\Models\City;
use App\Models\Department;
use App\Models\JobLevel;
use App\Models\Position;
use App\Models\Program;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use RamonRietdijk\LivewireTables\Columns\BooleanColumn;
use RamonRietdijk\LivewireTables\Columns\Column;
use RamonRietdijk\LivewireTables\Columns\SelectColumn;
use RamonRietdijk\LivewireTables\Filters\SelectFilter;
use RamonRietdijk\LivewireTables\Livewire\LivewireTable;

class PositionTable extends LivewireTable
{
    protected string $model = Position::class;

    public ?int $centerId = null;
    public ?int $cityId = null;

    public function mount(?int $centerId = null, ?int $cityId = null): void
    {
        $this->centerId = $centerId ?? request()->get('center');
        $this->cityId = $cityId ?? request()->get('city');
    }

    protected function query(): \Illuminate\Database\Eloquent\Builder
    {
        $query = $this->model()::query()->with(['city', 'program', 'center']);

        if ($this->centerId) {
            $query->where('center_id', $this->centerId);
        }
        if ($this->cityId) {
            $query->where('city_id', $this->cityId);
        }

        return $query;
    }

    protected function columns(): array
    {
        return [
            Column::make(__('Title'), 'title_en')->sortable()->searchable(),
            Column::make(__('Title') . ' (AR)', 'title_ar')->sortable()->searchable(),

            SelectColumn::make(trans_choice('general.city', 1), 'city_id')
                ->displayUsing(fn($v, Model $m) => $m->city->name ?? '-')
                ->options(City::pluck(transColumn('name'), 'id')->toArray())
                ->sortable(),

            SelectColumn::make(trans_choice('general.program', 1), 'program_id')
                ->displayUsing(fn($v, Model $m) => $m->program->name ?? '-')
                ->options(Program::pluck('name', 'id')->toArray())
                ->sortable(),

            SelectColumn::make(trans_choice('general.center', 1), 'center_id')
                ->displayUsing(fn($v, Model $m) => $m->center->name ?? '-')
                ->options(Center::pluck('name', 'id')->toArray())
                ->sortable(),

            SelectColumn::make(trans_choice('general.department', 1), 'department_id')
                ->displayUsing(fn($v, Model $m) => $m->department->name ?? '-')
                ->options(Department::pluck('name', 'id')->toArray())
                ->sortable(),

            SelectColumn::make(trans_choice('general.job-level', 1), 'job_level_id')
                ->displayUsing(fn($v, Model $m) => $m->jobLevel->name ?? '-')
                ->options(JobLevel::pluck(transColumn('name'), 'id')->toArray())
                ->sortable(),

            BooleanColumn::make(__('general.is-active'), 'is_active'),

            Column::make(__('Actions'), function (Model $m): string {
                $isActive = $m->is_active ? 1 : 0;

                $toggleBtn = Blade::render(
                    '<x-forms.toggle-input class="!me-2 text-xl p-2 mb-3"
                        active="' . $isActive . '"
                        :model-id="' . $m->id . '"
                        wire-event="confirmToggleItemStatus" />'
                );

                $editBtn = Blade::render(
                    '<x-table.edit-btn href="' . route('positions.edit', $m->id) . '" />'
                );

                $deleteBtn = Blade::render(
                    '<x-table.delete-btn :modelId="' . $m->id . '" />'
                );

                return $toggleBtn . $editBtn . $deleteBtn;
            })->asHtml(),
        ];
    }

    protected function filters(): array
    {
        return [
            SelectFilter::make(trans_choice('general.city', 1), 'city_id')
                ->options(City::pluck(transColumn('name'), 'id')->toArray()),

            SelectFilter::make(trans_choice('general.program', 1), 'program_id')
                ->options(Program::pluck('name', 'id')->toArray()),

            SelectFilter::make(trans_choice('general.center', 1), 'center_id')
                ->options(Center::pluck('name', 'id')->toArray()),

            SelectFilter::make(trans_choice('general.department', 1), 'department_id')
                ->options(Department::pluck('name', 'id')->toArray()),

            SelectFilter::make(trans_choice('general.job-level', 1), 'job_level_id')
                ->options(JobLevel::pluck(transColumn('name'), 'id')->toArray()),
        ];
    }

    protected function actions(): array
    {
        return [

        ];
    }

    public function confirmDelete($id)
    {
        LivewireAlert::title(__('general.delete-item'))
            ->text(__('general.confirm-delete-item'))
            ->question()
            ->withConfirmButton(__('general.delete'))
            ->onConfirm('delete', ['id' => $id])
            ->withCancelButton(__('general.cancel'))
            ->show();
    }

    public function delete($payload)
    {
        Position::findOrFail($payload['id'])->delete();

        LivewireAlert::title(__('general.success-message'))
            ->success()->toast()->position('top-start')->show();
    }

    public function confirmToggleItemStatus($id)
    {
        $this->itemID = $id;

        LivewireAlert::title(__('general.toggle-status'))
            ->text(__('general.confirm-status-change'))
            ->question()
            ->withConfirmButton(__('general.save'))
            ->onConfirm('toggleItemStatusConfirmed', ['id' => $id])
            ->withCancelButton(__('general.cancel'))
            ->show();
    }

    public function toggleItemStatusConfirmed($payload)
    {
        $item = Position::findOrFail($payload['id']);
        $item->is_active = !$item->is_active;
        $item->save();

        LivewireAlert::title(__('general.item-updated'))
            ->success()->toast()->position('top-start')->show();
    }

}
