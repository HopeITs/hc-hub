<?php

use App\Livewire\Concerns\Desk\IsDeskPanel;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Modules\Hr\Actions\Desk\SaveEmployeeNote;
use Modules\Hr\Models\EmployeeNote;

/**
 * My HR notes: the private notes an employee kept on the old HR dashboard
 * (employee_notes). The viewer adds, ticks and removes their own lines; done
 * ones sink below the open ones. Every write goes through SaveEmployeeNote,
 * held to the viewer's own employee record.
 */
new class extends Component {
    use IsDeskPanel;

    #[Locked]
    public string $card = 'hr-notes';

    /**
     * Adds a note at the end; true once it is saved.
     */
    public function add(string $content): bool
    {
        $employeeId = $this->ownerId();

        if ($employeeId === null) {
            return false;
        }

        app(SaveEmployeeNote::class)->add($employeeId, $content);
        unset($this->notes);

        return true;
    }

    public function toggle(int $id): void
    {
        if ($this->ownerId() !== null) {
            app(SaveEmployeeNote::class)->toggle($this->ownerId(), $id);
            unset($this->notes);
        }
    }

    public function remove(int $id): void
    {
        if ($this->ownerId() !== null) {
            app(SaveEmployeeNote::class)->remove($this->ownerId(), $id);
            unset($this->notes);
        }
    }

    /**
     * @return Collection<int, EmployeeNote>
     */
    #[Computed]
    public function notes(): Collection
    {
        $employeeId = $this->ownerId();

        return $employeeId === null
            ? new Collection
            : EmployeeNote::query()->select(['id', 'employee_id', 'content', 'is_done', 'order'])->forEmployee($employeeId)->orderBy('order')->orderBy('id')->limit(SaveEmployeeNote::MaxNotes)->get();
    }

    private function ownerId(): ?int
    {
        $employeeId = auth()->user()?->employee_id;

        return $employeeId === null ? null : (int) $employeeId;
    }
};
?>

@php
    [$done, $open] = $this->notes->partition(fn (EmployeeNote $note): bool => $note->is_done);
    $empty = $this->notes->isEmpty();
@endphp

<x-desk.panel :empty="$empty" :id="$card" :title="__('hr_desk.cards.notes.title')" :tagline="__('hr_desk.cards.notes.tagline')" class="dk-todo-panel" :filterable="false" :count="$open->count()" :size="$size" :cols="$gridCols" :height="$gridRows" :index="$index" :total="$total">
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
                maxlength="{{ SaveEmployeeNote::MaxLength }}"
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
                @foreach ([[$open, false], [$done, true]] as [$list, $isDone])
                    @if ($list->isNotEmpty())
                        <ul @class(['dk-todo-list', 'dk-todo-done' => $isDone]) aria-label="{{ $isDone ? __('hr_desk.cards.notes.done_list') : __('hr_desk.cards.notes.open_list') }}">
                            @foreach ($list as $note)
                                <li class="dk-todo-row" wire:key="hr-note-{{ $note->id }}">
                                    <span class="dk-todo-grip" aria-hidden="true"></span>
                                    <button type="button" class="dk-todo-check" role="checkbox" aria-checked="{{ $isDone ? 'true' : 'false' }}" wire:click="toggle({{ $note->id }})">
                                        <i aria-hidden="true">@if ($isDone)<x-desk.icon name="check" />@endif</i>
                                        <span class="dk-todo-t">{{ $note->content }}</span>
                                    </button>
                                    <button type="button" class="dk-iconbtn dk-sm dk-todo-x" wire:click="remove({{ $note->id }})" aria-label="{{ __('hr_desk.cards.notes.remove') }}"><x-desk.icon name="x" /></button>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
</x-desk.panel>
