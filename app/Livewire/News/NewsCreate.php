<?php

namespace App\Livewire\News;

use App\Livewire\Forms\NewsForm;
use App\Models\News;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Component;
use Livewire\WithFileUploads;

class NewsCreate extends Component
{
    use WithFileUploads;

    public NewsForm $form;

    public ?News $news = null;

    public function mount(?News $news = null): void
    {
        if ($news?->exists) {
            $this->news = $news;
            $this->form->fillFromNews($news);
        }
    }

    public function save()
    {
        $this->news = $this->form->persist($this->news);

        LivewireAlert::title(__('general.success-message'))->success()->toast()->position('top-start')->show();

        return to_route('news.index');
    }

    public function render()
    {
        return view('livewire.news.news-create');
    }
}
