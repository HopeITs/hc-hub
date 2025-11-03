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
    public $itemID;

    public function mount(?int $centerId = null, ?int $cityId = null): void
    {
        $this->centerId = $centerId ?? request()->get('center');
        $this->cityId = $cityId ?? request()->get('city');
    }

    protected function query(): \Illuminate\Database\Eloquent\Builder
    {
        $query = $this->model()::query()->with(['city', 'program', 'center' , 'jobLevel' , 'department']);

        if ($this->centerId) {
            $query->where('positions.center_id', $this->centerId);
        }
        if ($this->cityId) {
            $query->where('positions.city_id', $this->cityId);
        }

        return $query->orderBy('direct_manager_id')->orderBy('tech_manager_id');
    }

    protected function columns(): array
    {
        return [
            Column::make(__('Title'), 'title_en')->sortable()->searchable(),
            Column::make(__('Title') . ' (AR)', 'title_ar')->sortable()->searchable(),
            SelectColumn::make(trans_choice('general.city', 1), 'city.'.transColumn('name'))
                ->options(City::query()->get()->pluck(transColumn('name'), transColumn('name'))->toArray())
                ->sortable()     ->searchable(),
            SelectColumn::make(trans_choice('general.program', 1), 'program.name')
                ->options(Program::query()->get()->pluck('name', 'name')->toArray())
                ->sortable()     ->searchable(),
            SelectColumn::make(trans_choice('general.center', 1), 'center.name')
                ->options(Center::query()->get()->pluck('name', 'name')->toArray())
                ->sortable()     ->searchable(),
            SelectColumn::make(trans_choice('general.department', 1), 'department.name')
                ->options(Department::query()->get()->pluck('name', 'name')->toArray())
                ->sortable()     ->searchable(),
            SelectColumn::make(trans_choice('general.job-level', 1), 'jobLevel.'.transColumn('name'))
                ->options(JobLevel::query()->get()->pluck(transColumn('name'), transColumn('name'))->toArray())
                ->sortable()     ->searchable(),
            SelectColumn::make(__('general.direct-manager'), 'directManager.'.transColumn('title'))
                ->options(Position::query()->get()->pluck(transColumn('title'), transColumn('title'))->toArray())
                ->sortable()     ->searchable(),
            SelectColumn::make(__('general.tech-manager'), 'techManager.'.transColumn('title'))
                ->options(Position::query()->get()->pluck(transColumn('title'), transColumn('title'))->toArray())
                ->sortable()     ->searchable(),

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

    protected function actions(): array
    {
        return [];
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
