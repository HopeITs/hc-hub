<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\NoteVisibility;
use App\Models\Note;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Models\User;

/**
 * Notes follow the record they are about. A private note is its author's
 * alone, whatever the role. A context note is read by its author and by
 * anyone who can view the subject, in any module; adding one needs the same
 * view right, and removing one needs authorship or the right to update the
 * subject. A personal note (no subject) is only ever its author's.
 */
class NotePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Note $note): bool
    {
        if ($note->author_id === $user->id) {
            return true;
        }

        if ($note->visibility !== NoteVisibility::Context) {
            return false;
        }

        $subject = $this->subjectOf($note);

        return $subject !== null && $user->can('view', $subject);
    }

    /**
     * Add a note: a personal one for anyone signed in, or one about a record
     * the user can view.
     */
    public function create(User $user, ?Model $subject = null): bool
    {
        return $subject === null || $user->can('view', $subject);
    }

    public function update(User $user, Note $note): bool
    {
        return $note->author_id === $user->id;
    }

    public function delete(User $user, Note $note): bool
    {
        if ($note->author_id === $user->id) {
            return true;
        }

        $subject = $this->subjectOf($note);

        return $note->visibility === NoteVisibility::Context && $subject !== null && $user->can('update', $subject);
    }

    private function subjectOf(Note $note): ?Model
    {
        return $note->subject_type === null ? null : $note->subject;
    }
}
