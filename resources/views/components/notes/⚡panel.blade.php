<?php

use App\Enums\NoteVisibility;
use App\Models\Note;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * The one Notes panel every module embeds. With a subject (a morph-map key
 * and an id) it lists the notes about that record: the viewer's own, and
 * the context notes of others they may read. With none it is the viewer's
 * personal notebook. Every write is authorized through NotePolicy.
 */
new class extends Component {
    #[Locked]
    public ?string $subjectType = null;

    #[Locked]
    public ?int $subjectId = null;

    public string $body = '';

    /**
     * A note on a record starts private; ticking it off shares it with whoever can view the record.
     */
    public bool $onlyMe = true;

    public function mount(?string $subjectType = null, ?int $subjectId = null): void
    {
        $this->subjectType = $subjectType;
        $this->subjectId = $subjectId;

        $this->authorize('create', [Note::class, $this->subject()]);
    }

    public function add(): void
    {
        $subject = $this->subject();
        $this->authorize('create', [Note::class, $subject]);

        $this->body = trim($this->body);
        $this->validate(['body' => ['required', 'string', 'max:'.Note::MaxLength]], [
            'body.required' => __('notes.required'),
            'body.max' => __('notes.too_long', ['max' => Note::MaxLength]),
        ]);

        if ($this->ownNotes()->count() >= Note::MaxPerAuthor) {
            $this->addError('body', __('notes.full'));

            return;
        }

        Note::query()->create([
            'author_id' => auth()->id(),
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'body' => $this->body,
            'visibility' => $subject === null || $this->onlyMe ? NoteVisibility::Private : NoteVisibility::Context,
        ]);

        $this->reset('body');
        unset($this->notes);
    }

    public function remove(int $id): void
    {
        $note = $this->scoped()->findOrFail($id);
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
        $viewerId = (int) auth()->id();

        return $this->scoped()
            ->when($this->subjectType !== null, fn (Builder $query) => $query->where(fn (Builder $inner) => $inner
                ->where('author_id', $viewerId)
                ->orWhere('visibility', NoteVisibility::Context->value)))
            ->with('author')
            ->latest()
            ->latest('id')
            ->limit(Note::MaxPerAuthor)
            ->get();
    }

    /**
     * The record the notes are about; null for the personal notebook.
     */
    private function subject(): ?Model
    {
        if ($this->subjectType === null) {
            return null;
        }

        $class = Relation::getMorphedModel($this->subjectType);

        abort_if($class === null || $this->subjectId === null, 404);

        return $class::query()->findOrFail($this->subjectId);
    }

    /**
     * @return Builder<Note>
     */
    private function scoped(): Builder
    {
        if ($this->subjectType === null) {
            return Note::query()->personalFor((int) auth()->id());
        }

        return Note::query()->where('subject_type', $this->subjectType)->where('subject_id', $this->subjectId);
    }

    /**
     * @return Builder<Note>
     */
    private function ownNotes(): Builder
    {
        return $this->scoped()->where('author_id', auth()->id());
    }
};
?>


<x-ui.panel :title="__('notes.title')" :count="$this->notes->count()" data-test="notes-panel">
    <form class="space-y-3 border-b border-panel-border p-4" wire:submit="add">
        <x-ui.form-textarea :label="__('notes.label')" wire:model="body" rows="2" maxlength="{{ Note::MaxLength }}" placeholder="{{ __('notes.placeholder') }}" data-test="note-body" />

        <div class="flex flex-wrap items-center justify-between gap-3">
            @if ($subjectType !== null)
                <label class="inline-flex items-center gap-2 text-sm text-secondary-medium dark:text-ink-muted">
                    <input type="checkbox" wire:model="onlyMe" class="rounded border-panel-border">
                    {{ __('notes.only_me') }}
                </label>
            @else
                <p class="text-xs text-secondary-medium dark:text-ink-muted">{{ __('notes.personal_hint') }}</p>
            @endif
            <x-ui.button tone="primary" size="sm" icon="plus" type="submit" wire:loading.attr="disabled" wire:target="add" data-test="add-note">{{ __('notes.add') }}</x-ui.button>
        </div>
    </form>

    @if ($this->notes->isEmpty())
        <p class="p-4 text-sm text-secondary-medium dark:text-ink-muted">{{ __('notes.empty') }}</p>
    @else
        <ul class="divide-y divide-panel-border">
            @foreach ($this->notes as $note)
                <li class="flex items-start gap-3 px-4 py-3" wire:key="note-{{ $note->id }}" data-test="note-row">
                    <div class="min-w-0 flex-1">
                        <p class="whitespace-pre-line break-words text-sm text-secondary-dark dark:text-ink">{{ $note->body }}</p>
                        <p class="mt-1 text-xs text-secondary-medium dark:text-ink-muted">
                            @if ($subjectType !== null)
                                {{ $note->author?->name }} ·
                            @endif
                            {{ $note->created_at?->diffForHumans() }}
                            @if ($subjectType !== null && $note->visibility === NoteVisibility::Private)
                                · {{ __('notes.private_badge') }}
                            @endif
                        </p>
                    </div>
                    @can('delete', $note)
                        <x-ui.button tone="ghost" size="sm" icon="trash" wire:click="remove({{ $note->id }})" wire:confirm="{{ __('notes.confirm_remove') }}" :aria-label="__('notes.remove')" data-test="remove-note" />
                    @endcan
                </li>
            @endforeach
        </ul>
    @endif
</x-ui.panel>
