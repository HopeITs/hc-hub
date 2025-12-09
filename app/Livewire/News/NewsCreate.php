<?php

namespace App\Livewire\News;

use App\Models\News;
use App\Models\City;
use App\Models\Center;
use App\Models\Program;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Livewire\Component;
use Livewire\WithFileUploads;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;

class NewsCreate extends Component
{
    use WithFileUploads;

    public array $item = [
        'title' => '',
        'description' => '',
        'photo' => null,
        'display_until' => null,
        'event_date' => null,
        'newsable_type' => '',
        'newsable_id' => '',
    ];

    protected $rules = [
        'item.title' => 'required|string|max:255',
        'item.description' => 'required|string',
        'item.newsable_type' => 'nullable|string',
        'item.newsable_id' => 'nullable|integer',
        'item.display_until' => 'nullable|date',
        'item.event_date' => 'nullable|date',
        'item.photo' => 'nullable|image|max:2048',
    ];

    public function getEntitiesProperty()
{
    return match($this->item['newsable_type'] ?? '') {
        City::class => City::all(),
        Center::class => Center::select('id', 'name')->get(),
        Program::class => Program::select('id', 'name')->get(),
        Employee::class => Employee::all(),
        default => collect(),
    };
}

    public function updatedItemNewsableType($value): void
    {
        $this->item['newsable_id'] = '';
    }

    public function save()
    {
        $this->validate();

        $news = new News([
            'title' => $this->item['title'],
            'description' => $this->item['description'],
            'newsable_type' => $this->item['newsable_type'] ?: null,
            'newsable_id' => $this->item['newsable_id'] ?: null,
            'display_until' => $this->item['display_until'],
            'event_date' => $this->item['event_date'],
            'published_at' => now(),
        ]);

        $news->save();

        if ($this->item['photo'] instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
            $news->addMedia($this->item['photo'])->toMediaCollection('news-image');
        }

        LivewireAlert::title(__('general.success-message'))->success()->toast()->position('top-start')->show();

        return redirect()->route('news.index');
    }

    public function render()
    {
        return view('livewire.news.news-create', [
            'cities' => City::select('id')->get(),
            'centers' => Center::select('id', 'name')->get(),
            'programs' => Program::select('id', 'name')->get(),
            'employees' => Employee::select('id')->get(),
        ]);
    }
}
