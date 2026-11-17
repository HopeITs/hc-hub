<?php

use App\Enums\NoteVisibility;
use App\Livewire\Concerns\Desk\IsDeskPanel;
use App\Models\Note;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * My notes: the viewer's personal notebook on the shared `notes` table (no
 * subject, private). The panel keeps its old id (hr-notes) because saved
 * Desk layouts store panel ids. Every write is held to the viewer's own
 * personal notes, so another person's note id finds nothing.
 */
new class extends Component {
    use IsDeskPanel;

    #[Locked]
    public string $card = 'hr-notes';

    /**
     * Adds a note; true once it is saved.
     */
    public function add(string $content): bool
    {
        $this->authorize('create', Note::class);

        $content = trim($content);

        Validator::make(
            ['content' => $content],
            ['content' => ['required', 'string', 'max:'.Note::MaxLength]],
            [
                'content.required' => __('notes.required'),
                'content.max' => __('notes.too_long', ['max' => Note::MaxLength]),
            ],
        )->after(function ($validator): void {
            if ($this->personal()->count() >= Note::MaxPerAuthor) {
                $validator->errors()->add('content', __('notes.full'));
            }
        })->validate();

        Note::query()->create([
            'author_id' => auth()->id(),
            'body' => $content,
            'visibility' => NoteVisibility::Private,
        ]);
        unset($this->notes);

        return true;
    }

    public function remove(int $id): void
    {
        $note = $this->personal()->findOrFail($id);
        $this->authorize('delete', $note);

        $note->delete();
        unset($this->notes);
    }

    /**
     * @return Collection<int, Note>
     */
    #[Computed]
    public function notes(): Collection
    {
        return $this->personal()->select(['id', 'author_id', 'body', 'created_at'])->latest()->latest('id')->limit(Note::MaxPerAuthor)->get();
    }

    /**
     * @return Builder<Note>
     */
    private function personal(): Builder
    {
        return Note::query()->personalFor((int) auth()->id());
    }
};
?>


@php
    $empty = $this->notes->isEmpty();
@endphp

<x-desk.panel :empty="$empty" :id="$card" :title="__('hr_desk.cards.notes.title')" :tagline="__('hr_desk.cards.notes.tagline')" class="dk-todo-panel" :filterable="false" :count="$this->notes->count()" :size="$size" :cols="$gridCols" :height="$gridRows" :index="$index" :total="$total">
    <div class="dk-todo" x-data="{
        draft: '',
        save() { $wire.add(this.draft).then((saved) => { if (saved === true) { this.draft = '' } }) },
    }">
        <form class="dk-todo-add" x-on:submit.prevent="save()">
            <label for="dk-hr-note-{{ $index }}" class="sr-only">{{ __('hr_desk.cards.notes.label') }}</label>
            <input
                id="dk-hr-note-{{ $index }}"
                type="text"
                class="dk-todo-input"
                x-model="draft"
                maxlength="{{ Note::MaxLength }}"
                autocomplete="off"
                placeholder="{{ __('hr_desk.cards.notes.placeholder') }}"
                @error('content') aria-invalid="true" aria-describedby="dk-hr-note-err-{{ $index }}" @enderror
            >
            <button type="submit" class="dk-iconbtn dk-sm" aria-label="{{ __('hr_desk.cards.notes.add') }}" title="{{ __('hr_desk.cards.notes.add') }}" wire:loading.attr="disabled" wire:target="add"><x-desk.icon name="plus" /></button>
        </form>
        @error('content')
            <p id="dk-hr-note-err-{{ $index }}" class="dk-todo-err dk-bad" role="alert">{{ $message }}</p>
        @enderror

        @if ($empty)
            <p class="dk-todo-empty">{{ __('hr_desk.cards.notes.empty') }}</p>
        @else
            <div class="dk-todo-scroll">
                <ul class="dk-todo-list" aria-label="{{ __('hr_desk.cards.notes.title') }}">
                    @foreach ($this->notes as $note)
                        <li class="dk-todo-row" wire:key="my-note-{{ $note->id }}">
                            <span class="dk-todo-grip" aria-hidden="true"></span>
                            <span class="dk-todo-t min-w-0 flex-1 break-words" data-test="my-note">{{ $note->body }}</span>
                            <button type="button" class="dk-iconbtn dk-sm dk-todo-x" wire:click="remove({{ $note->id }})" aria-label="{{ __('hr_desk.cards.notes.remove') }}"><x-desk.icon name="x" /></button>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</x-desk.panel>
