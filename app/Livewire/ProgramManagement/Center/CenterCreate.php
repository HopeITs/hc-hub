<?php

namespace App\Livewire\ProgramManagement\Center;

use App\Models\Center;
use App\Models\City;
use App\Notifications\NewCenterCreated;
use Illuminate\Support\Facades\Validator;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Component;

class CenterCreate extends Component
{
    public array $item = [
        'name' => '',
        'city_id' => '',
        'address_ar' => '',
        'address_en' => '',
        'is_active' => true,
        'weekends' => [],
        'open_at' => null,
        'close_at' => null,
    ];

    protected $rules = [
        'item.name' => 'required|string|max:255',
        'item.city_id' => 'required|exists:cities,id',
        'item.address_en' => 'nullable|string|max:255',
        'item.address_ar' => 'nullable|string|max:255',
        'item.weekends' => 'required|array',
        'item.open_at' => 'required',
        'item.close_at' => 'required',
    ];

    public function save()
    {
        $this->validate();

        $this->item['weekends'] = implode(',', $this->item['weekends']);
        $center = Center::create($this->item);

        foreach (\App\Models\User::all() as $user) {
            $user->notify(new NewCenterCreated($center));
        }

        LivewireAlert::title(__('general.success-message'))
            ->success()
            ->toast()
            ->position('top-start')
            ->show();

        return redirect()->route('centers.index');
    }

    public function render()
    {
        $cities = City::get(['id', transColumn('name')]);

        return view('livewire.program-management.center.center-create', compact('cities'));
    }
}
