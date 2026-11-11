<?php

use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Hr\Actions\News\SaveNews;
use Modules\Hr\Enums\News\AudienceType;
use Modules\Hr\Enums\News\NewsType;
use Modules\Hr\Livewire\Concerns\News\ManagesNewsForm;
use Modules\Hr\Models\News;

/**
 * Write or change a news item, announcement or event: both languages, tags,
 * who sees it, when it goes out, the event's times, when it stops showing,
 * and a cover picture. "Save draft" keeps it hidden; "Publish" sends it out
 * now or at the chosen time. Rules live in ManagesNewsForm, the write in
 * SaveNews, the permission in NewsPolicy.
 */
new #[Layout('layouts::desk'), Title('hr_news.titles.form')] class extends Component {
    use ManagesNewsForm;
    use WithFileUploads;

    #[Locked]
    public ?int $newsId = null;

    public function mount(?News $news = null): void
    {
        if ($news?->exists) {
            Gate::authorize('update', $news);
            $this->newsId = (int) $news->id;
            $this->fillNewsForm($news);

            return;
        }

        Gate::authorize('create', News::class);
    }

    #[Computed]
    public function news(): ?News
    {
        return $this->newsId === null ? null : News::query()->findOrFail($this->newsId);
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function targets(): array
    {
        return $this->audienceOptions();
    }

    public function save(string $intent): void
    {
        $intent = $intent === SaveNews::Publish ? SaveNews::Publish : SaveNews::Draft;
        $news = $this->news;

        $news !== null ? Gate::authorize('update', $news) : Gate::authorize('create', News::class);

        $values = $this->validatedNews($news);
        $saved = app(SaveNews::class)->handle(auth()->user(), $news, $values, $intent, $this->photo);

        Flux::toast(variant: 'success', text: __($intent === SaveNews::Publish ? 'hr_news.form.published' : 'hr_news.form.saved_draft'));

        $this->redirectRoute('hr.news.preview', ['news' => $saved->id], navigate: true);
    }

}; ?>

@php
    $isEvent = $type === NewsType::Event->value;
    $audience = AudienceType::tryFrom($audienceType) ?? AudienceType::All;
    $cover = $this->news?->coverUrl();
@endphp

<x-desk.register heading-id="hr-news-form-heading" :title="__($newsId === null ? 'hr_news.titles.create' : 'hr_news.titles.edit')">
    <x-slot:actions>
        <a href="{{ route('hr.news.index') }}" wire:navigate class="dk-btn">{{ __('common.back') }}</a>
    </x-slot:actions>

    <form wire:submit="save('publish')" class="grid max-w-4xl gap-8" novalidate>
        <x-ui.unsaved-guard :watch="['type', 'titleAr', 'titleEn', 'descriptionAr', 'descriptionEn', 'tagLine', 'audienceType', 'audienceIds', 'publishMode', 'publishAt', 'eventStartsAt', 'eventEndsAt', 'displayUntil', 'photo']" />

        <x-ui.form-section :title="__('hr_news.form.sections.what')" :fields="['type', 'titleAr', 'titleEn', 'descriptionAr', 'descriptionEn', 'tagLine']">
            <x-ui.form-segmented
                :label="__('hr_news.form.fields.type')"
                wire:model.live="type"
                :options="collect(NewsType::cases())->map(fn (NewsType $case): array => ['value' => $case->value, 'label' => __('hr_news.types.'.$case->value)])->all()"
                required
            />
            <x-ui.form-input :label="__('hr_news.form.fields.title_ar')" wire:model="titleAr" maxlength="255" dir="rtl" required />
            <x-ui.form-input :label="__('hr_news.form.fields.title_en')" wire:model="titleEn" maxlength="255" dir="ltr" />
            <x-ui.form-textarea :label="__('hr_news.form.fields.description_ar')" wire:model="descriptionAr" rows="6" dir="rtl" required />
            <x-ui.form-textarea :label="__('hr_news.form.fields.description_en')" wire:model="descriptionEn" rows="6" dir="ltr" />
            <x-ui.form-input :label="__('hr_news.form.fields.tags')" wire:model="tagLine" :help="__('hr_news.form.tags_help', ['max' => News::MAX_TAGS])" />
        </x-ui.form-section>

        <x-ui.form-section :title="__('hr_news.form.sections.who')" :fields="['audienceType', 'audienceIds', 'audienceIds.*']">
            <x-ui.form-select :label="__('hr_news.form.fields.audience_type')" wire:model.live="audienceType" :value="$audienceType" required>
                @foreach (AudienceType::cases() as $case)
                    <option value="{{ $case->value }}">{{ __('hr_news.audience.'.$case->value) }}</option>
                @endforeach
            </x-ui.form-select>

            @if ($audience->needsTargets())
                <x-ui.form-multiselect :label="__('hr_news.form.fields.audience_ids')" wire:model="audienceIds" :value="$audienceIds" searchable required wire:key="audience-{{ $audience->value }}">
                    @foreach ($this->targets as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </x-ui.form-multiselect>
            @endif
        </x-ui.form-section>

        <x-ui.form-section :title="__('hr_news.form.sections.when')" :fields="['publishMode', 'publishAt', 'eventStartsAt', 'eventEndsAt', 'displayUntil']">
            <x-ui.form-segmented
                :label="__('hr_news.form.fields.publish_mode')"
                wire:model.live="publishMode"
                :options="[['value' => 'now', 'label' => __('hr_news.form.publish_now')], ['value' => 'schedule', 'label' => __('hr_news.form.publish_later')]]"
            />

            @if ($publishMode === 'schedule')
                <x-ui.form-input type="datetime-local" :label="__('hr_news.form.fields.publish_at')" wire:model="publishAt" required />
            @endif

            @if ($isEvent)
                <x-ui.form-input type="datetime-local" :label="__('hr_news.form.fields.event_starts_at')" wire:model="eventStartsAt" required />
                <x-ui.form-input type="datetime-local" :label="__('hr_news.form.fields.event_ends_at')" wire:model="eventEndsAt" />
            @endif

            <x-ui.form-input type="datetime-local" :label="__('hr_news.form.fields.display_until')" wire:model="displayUntil" :help="__('hr_news.form.display_until_help')" />
        </x-ui.form-section>

        <x-ui.form-section :title="__('hr_news.form.sections.cover')" :fields="['photo']">
            @if ($cover !== null && $photo === null)
                <img src="{{ $cover }}" alt="" class="max-h-48 rounded-lg object-cover" loading="lazy" />
            @endif
            <x-ui.form-file :label="__('hr_news.form.fields.photo')" wire:model="photo" accept="image/jpeg,image/png,image/gif,image/webp" types="JPG, PNG, GIF, WEBP" max-size="2 MB" />
        </x-ui.form-section>

        <div class="flex flex-wrap justify-end gap-3">
            <x-ui.button type="button" tone="ghost" wire:click="save('draft')" data-test="hr-news-save-draft">{{ __('hr_news.form.save_draft') }}</x-ui.button>
            <x-ui.button type="submit" tone="primary" data-test="hr-news-publish">{{ $publishMode === 'schedule' ? __('hr_news.form.schedule') : __('hr_news.form.publish') }}</x-ui.button>
        </div>
    </form>
</x-desk.register>
