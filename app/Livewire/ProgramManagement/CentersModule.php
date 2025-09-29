<?php

namespace App\Livewire\ProgramManagement;

use App\Models\Center;
use App\Models\City;
use App\Models\Program;
use Livewire\Component;
use Livewire\WithPagination;
use App\Traits\SortableTable;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;

class CentersModule extends Component
{
    use WithPagination, SortableTable;

    public array $item = [
        'id' => null,
        'name' => '',
        'city_id' => '',
        'address_en' => '',
        'address_ar' => '',
        'is_active' => true,
    ];

    protected $rules = [
        'item.name' => 'required|string|max:255',
        'item.city_id' => 'required|exists:cities,id',
        'item.address_en' => 'nullable|string|max:255',
        'item.address_ar' => 'nullable|string|max:255',
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function confirmToggleItemStatus($id)
    {
        $this->itemID = $id;

        LivewireAlert::title(__('general.toggle-status'))
            ->text(__('general.confirm-status-change'))
            ->question()
            ->confirmButtonColor(config('samples.styles.colors.confirm'))
            ->withConfirmButton(__('general.save'))
            ->onConfirm('toggleItemStatusConfirmed', ['id' => $id])
            ->withCancelButton(__('general.cancel'))
            ->show();
    }

    public function toggleItemStatusConfirmed()
    {
        $item = Center::findOrFail($this->itemID);
        $item->is_active = !$item->is_active;
        $item->save();

        LivewireAlert::title(__('general.item-updated'))
            ->success()->toast()->position('top-start')->show();
    }

    public function render()
    {
        $locale = app()->getLocale();
        $sort = $this->sortField ?: 'id';
        $dir = $this->sortDirection ?: 'desc';

        $query = Center::query()
            ->with(['city', 'program'])
            ->when($this->search, function ($q) use ($locale) {
                $term = $this->search;
                $q->where(function ($w) use ($term, $locale) {
                    $w->where('name', 'like', "%{$term}%")
                      ->orWhere("address->$locale", 'like', "%{$term}%")
                      ->orWhereHas('city', function ($c) use ($term, $locale) {
                          $c->where("name->$locale", 'like', "%{$term}%");
                      })
                      ->orWhereHas('program', function ($p) use ($term) {
                          $p->where('name', 'like', "%{$term}%");
                      });
                });
            });

        if ($sort === 'address') {
            $query->orderBy("address->$locale", $dir);
        } elseif ($sort === 'city_name') {
            $query->leftJoin('cities', 'centers.city_id', '=', 'cities.id')
                  ->orderBy("cities.name->$locale", $dir)
                  ->select('centers.*');
        } elseif ($sort === 'program_name') {
            $query->leftJoin('programs', 'centers.program_id', '=', 'programs.id')
                  ->orderBy('programs.name', $dir)
                  ->select('centers.*');
        } else {
            $query->orderBy($sort, $dir);
        }

        $items = $query->paginate(15);

        $cities = City::orderBy("name->$locale")->get(['id', 'name']);
        $programs = Program::orderBy('name')->get(['id', 'name']);

        return view('livewire.program-management.centers-module', compact('cities'));
    }

    public function create(): void
    {
        $this->item = [
            'id'         => null,
            'name'       => '',
            'city_id'    => '',
            'program_id' => '',
            'address'    => ['en' => '', 'ar' => ''],
            'is_active'  => true,
        ];

        $this->resetValidation();
        $this->showModal = true;
        $this->modalTitle = __('general.add-new');
        $this->showModal = true;
    }

    #[On('openCenterEdit')]
    public function edit(int $id): void
    {
        $model = Center::findOrFail($id);

        $this->modalTitle = __('general.edit');
        $this->item = [
            'id'         => $model->id,
            'name'       => $model->name,
            'city_id'    => $model->city_id,
            'program_id' => $model->program_id,
            'address_en' => $model->address_en,
            'address_ar' => $model->address_ar,
            'is_active'  => (bool) $model->is_active,
        ];

        $this->resetValidation();
        $this->modalTitle = __('general.edit');
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        Center::updateOrCreate(
            ['id' => $this->item['id']],
            [
                'name' => $this->item['name'],
                'city_id' => $this->item['city_id'],
                'program_id' => $this->item['program_id'],
                'address_en' => $this->item['address_en'],
                'address_ar' => $this->item['address_ar'],
            ]
        );

        $this->showModal = false;
        $this->resetErrorBag();
        $this->resetValidation();
        $this->dispatch('closeModal');

        LivewireAlert::title(__('general.success-message'))
            ->success()->toast()->position('top-start')->show();
    }
}
