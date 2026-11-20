@props(['subject'])

{{--
    The Notes panel of a record page (4.14): any record whose model is in the
    morph map and has a policy. NotePolicy decides: whoever may view the record
    reads its context notes and may add one; a private note stays its author's.
    Someone who may not view the record gets no panel at all.
--}}
@can('create', [\App\Models\Note::class, $subject])
    <section {{ $attributes->class('grid gap-3') }} data-record-notes="{{ $subject->getMorphClass() }}">
        <livewire:notes.panel :subject-type="$subject->getMorphClass()" :subject-id="(int) $subject->getKey()" :key="'record-notes-'.$subject->getMorphClass().'-'.$subject->getKey()" />
    </section>
@endcan
