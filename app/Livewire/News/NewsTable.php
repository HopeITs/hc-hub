<?php

namespace App\Livewire\News;

use App\Models\News;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use RamonRietdijk\LivewireTables\Columns\Column;
use RamonRietdijk\LivewireTables\Enums\Direction;
use RamonRietdijk\LivewireTables\Livewire\LivewireTable;

class NewsTable extends LivewireTable
{
    protected string $model = News::class;

    protected $listeners = ['delete', 'refreshNewsTable' => '$refresh'];

    protected function query(): Builder
    {
        $employee = auth()->user()?->profile;

        return News::query()
            ->with('newsable')
            ->when($employee && method_exists(News::class, 'scopeVisibleTo'), fn (Builder $q) => $q->visibleTo($employee))
            ->latest();
    }

    protected function globalSearchColumns(): array
    {
        return ['title', 'description'];
    }

    protected function columns(): array
    {
        return [
            Column::make('ID', 'id')->sortable(),
            Column::make(__('general.title'), 'title')->sortable()->searchable(),
            Column::make(__('general.target_group'), 'newsable_name')
                ->sortable(fn (Builder $query, Direction $direction) => $query->orderBy('newsable_type', $direction->value)),
            Column::make(__('general.display_until'), 'display_until')
                ->displayUsing(fn ($v) => $v ? $v->format('Y-m-d') : __('general.always_display'))
                ->sortable(),
            Column::make(__('general.event_date'), 'event_date')
                ->displayUsing(fn ($v) => $v ? $v->format('Y-m-d') : __('general.n_a'))
                ->sortable(),
            Column::make(__('general.published_at'), 'published_at')
                ->displayUsing(fn ($v) => optional($v)->diffForHumans())
                ->sortable(),
            Column::make(__('general.actions'), function (Model $m): string {
                $id = $m->getKey();
                $editUrl = route('news.edit', ['news' => $id]);
                $editBtn = Blade::render('<x-table.edit-btn href="'.$editUrl.'" />');
                $deleteBtn = Blade::render('<x-table.delete-btn wire:click="confirmDelete('.$id.')" :modelId="'.$id.'" />');

                return '<div class="flex items-center justify-center gap-2">'.$editBtn.$deleteBtn.'</div>';
            })->asHtml(),
        ];
    }

    public function confirmDelete(int $id): void
    {
        LivewireAlert::title(__('general.delete-item'))
            ->text(__('general.confirm-delete-item'))
            ->question()
            ->withConfirmButton(__('general.delete'))
            ->onConfirm('delete', ['id' => $id])
            ->withCancelButton(__('general.cancel'))
            ->show();
    }

    public function delete(array $payload): void
    {
        News::findOrFail($payload['id'])->delete();
        LivewireAlert::title(__('general.success-message'))->success()->toast()->position('top-start')->show();
        $this->dispatch('refreshNewsTable');
    }
}
