<?php

namespace App\Livewire\News;

use App\Livewire\Forms\NewsForm;
use App\Models\News;
use App\Services\TranslationService;
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
            abort_unless($this->canManageNews($news), 403);

            $this->news = $news;
            $this->form->fillFromNews($news);

            return;
        }

        abort_unless($this->canCreateNews(), 403);
    }

    public function updatedFormNewsableType(): void
    {
        $this->form->updatedNewsableType();
    }

    public function save()
    {
        abort_unless(
            $this->news ? $this->canManageNews($this->news) : $this->canCreateNews(),
            403
        );

        $this->news = $this->form->persist($this->news);

        LivewireAlert::title(__('general.success-message'))->success()->toast()->position('top-start')->show();

        return to_route('news.index');
    }

    public function translateTitleToEnglish(): void
    {
        $this->translateArabicFieldToEnglish('title_ar', 'title_en');
    }

    public function translateDescriptionToEnglish(): void
    {
        $this->translateArabicFieldToEnglish('description_ar', 'description_en');
    }

    public function render()
    {
        return view('livewire.news.news-create');
    }

    private function canCreateNews(): bool
    {
        return $this->canManageAllNews() || $this->isManager();
    }

    private function translateArabicFieldToEnglish(string $sourceField, string $targetField): void
    {
        $source = (string) $this->form->{$sourceField};

        if (trim($source) === '') {
            LivewireAlert::title(__('general.translation-source-required'))->warning()->toast()->position('top-start')->show();

            return;
        }

        $translated = app(TranslationService::class)->arabicToEnglish($source);

        if (! $translated) {
            LivewireAlert::title(__('general.translation-failed'))->error()->toast()->position('top-start')->show();

            return;
        }

        $this->form->{$targetField} = $translated;
    }

    private function canManageNews(News $news): bool
    {
        return $this->canManageAllNews()
            || ($this->isManager() && (int) $news->created_by === (int) auth()->id());
    }

    private function canManageAllNews(): bool
    {
        return auth()->user()?->activeRoleIs('HR', 'super-admin', 'general-manager') ?? false;
    }

    private function isManager(): bool
    {
        return auth()->user()?->activeRoleIs('manager') ?? false;
    }
}
