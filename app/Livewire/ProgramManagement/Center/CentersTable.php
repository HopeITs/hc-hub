<?php

namespace App\Livewire\ProgramManagement\Center;

use App\Models\Center;
use App\Models\City;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use RamonRietdijk\LivewireTables\Columns\BooleanColumn;
use RamonRietdijk\LivewireTables\Columns\Column;
use RamonRietdijk\LivewireTables\Columns\SelectColumn;
use RamonRietdijk\LivewireTables\Filters\SelectFilter;
use RamonRietdijk\LivewireTables\Livewire\LivewireTable;

class CentersTable extends LivewireTable
{
    protected string $model = Center::class;

    protected $listeners = ['delete', 'toggleItemStatusConfirmed'];

    protected function query(): Builder
    {
        return $this->model()
            ::query()
            ->with(['city'])
            ->withCount('positions');
    }

    protected function globalSearchColumns(): array
    {
        return ['name'];
    }

    protected function cityOptions(): array
    {
        $locale = app()->getLocale();

        return City::query()->whereHas('centers')
            ->orderBy(transColumn('name'))
            ->get(['id', transColumn('name')])
            ->mapWithKeys(fn($c) => [$c->id => $c->name])
            ->all();
    }

    protected function columns(): array
    {
        $locale = app()->getLocale();
        $user = auth()->user();
        $isHR = $user?->activeRoleIs('HR') || $user?->activeRoleIs('super-admin') ;

        return [
            Column::make('ID', 'id')->sortable(),

            Column::make(__('general.center-name'), 'name')
                ->sortable()->searchable(),
            Column::make(__('general.working-hours'), 'open_at')
                ->displayUsing(fn($v, Model $m) => __('general.from-hour-to-hour', [
                    'from' => $m->open_at_formatted,
                    'to' => $m->close_at_formatted,
                ]))->sortable(),

            SelectColumn::make(trans_choice('general.city', 1), 'city.' . transColumn('name'))
                ->options(City::query()
                    ->whereNot('code', '000')
                    ->where('is_active', true)
                    ->get()
                    ->pluck(transColumn('name'), transColumn('name'))
                    ->toArray()
                )
                ->sortable()
                ->searchable(),

            Column::make(__('general.address'))
                ->displayUsing(fn($v, Model $m) => $m->address ?: '—')
                ->sortable()
                ->searchable(),

            BooleanColumn::make(__('general.is-active'), 'is_active')->searchable(),

            // ✅ Positions button with count
            Column::make(__('general.positions'), function (Model $m): string {
                $url = route('positions.index', ['center' => $m->id]);

                $classes = 'inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs md:text-sm font-medium ' . 'border shadow-sm transition focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-primary/40 ' . ($m->is_active ? 'bg-primary/10 text-primary border-primary/20 hover:bg-primary/15 hover:border-primary/30' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50 hover:border-gray-300');

                $count = $m->positions_count ?? 0;
                $badge = '<span class="inline-flex items-center justify-center min-w-5 h-5 px-1.5 rounded-full text-[11px] md:text-xs bg-current/10">' . $count . '</span>';

                return Blade::render(
                    '<a href="' . $url . '" class="' . $classes . '" title="' . e(__('general.positions')) . '">
                        <i class="ti ti-briefcase text-base"></i>
                        <span>' . e(trans_choice('general.positions', 2)) . '</span>' .
                    $badge .
                    '</a>'
                );
            })->asHtml()->visible($user->activeRoleIs('HR') || $user->activeRoleIs('super-admin')),

            Column::make(__('general.actions'), function (Model $m) use ($isHR): string {
                $viewUrl = route('centers.show', $m->getKey());
                $viewBtn = Blade::render(
                    '<a href="' . $viewUrl . '"
                                class="inline-flex items-center justify-center p-2 text-xl rounded-lg  text-black"
                                title="' . e(__('general.view')) . '">
                                <svg xmlns="http://www.w3.org/2000/svg"
                                     fill="none" viewBox="0 0 24 24"
                                     stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M2.25 12s3.75-7.5 9.75-7.5 9.75 7.5 9.75 7.5-3.75 7.5-9.75 7.5S2.25 12 2.25 12z" />
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </a>'
                );

                if (!$isHR) {
                    return $viewBtn;
                }

                $toggleBtn = Blade::render(
                    '<x-forms.toggle-input class="!me-2 text-xl p-2 mb-3" active="' . ($m->is_active ? 1 : 0) . '" :model-id="' . $m->getKey() . '" wire-event="confirmToggleItemStatus" />'
                );

                $editBtn = Blade::render(
                    '<x-table.edit-btn wire:click="$dispatch(\'openCenterEdit\', { id: ' . $m->getKey() . ' })" />'
                );

                $deleteBtn = Blade::render(
                    '<x-table.delete-btn :modelId="' . $m->getKey() . '" />'
                );

                return $toggleBtn . $viewBtn . $editBtn ;
            })->asHtml(),
        ];
    }



    public function confirmToggleItemStatus($id): void
    {
        LivewireAlert::title(__('general.toggle-status'))
            ->text(__('general.confirm-status-change'))
            ->question()
            ->withConfirmButton(__('general.save'))
            ->onConfirm('toggleItemStatusConfirmed', ['id' => $id])
            ->withCancelButton(__('general.cancel'))
            ->show();
    }

    public function toggleItemStatusConfirmed($payload): void
    {
        $item = Center::findOrFail($payload['id']);
        $item->is_active = !$item->is_active;
        $item->save();

        if (!$item->is_active) {
            foreach (\App\Models\User::all() as $user) {
                $user->notify(new \App\Notifications\CenterClosed($item));
            }
            $this->dispatch('center-closed');
        }

        LivewireAlert::title(__('general.item-updated'))->success()->toast()->position('top-start')->show();
    }

    public function confirmDelete($id): void
    {
        LivewireAlert::title(__('general.delete-item'))
            ->text(__('general.confirm-delete-item'))
            ->question()
            ->withConfirmButton(__('general.delete'))
            ->onConfirm('delete', ['id' => $id])
            ->withCancelButton(__('general.cancel'))
            ->show();
    }

    public function delete($payload): void
    {
        Center::findOrFail($payload['id'])->delete();

        LivewireAlert::title(__('general.success-message'))->success()->toast()->position('top-start')->show();
    }
}
