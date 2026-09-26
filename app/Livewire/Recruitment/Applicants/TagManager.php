<?php

namespace App\Livewire\Recruitment\Applicants;

use App\Models\RecruitmentApplication;
use App\Models\RecruitmentTag;
use App\Services\RecruitmentTagService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * The shared tag list: create, rename, delete, restore.
 *
 * Counts include only applications the viewer can see, so the list says
 * nothing about how a tag is used in another governorate.
 */
class TagManager extends Component
{
    public string $tagName = '';

    public string $tagColor = 'neutral';

    #[Locked]
    public ?int $renamingId = null;

    public string $renameName = '';

    public string $renameColor = 'neutral';

    public function create(RecruitmentTagService $tags): void
    {
        Gate::authorize('create', RecruitmentTag::class);

        if ($this->run(fn () => $tags->create(auth()->user(), $this->tagName, $this->tagColor))) {
            $this->reset('tagName', 'tagColor');
        }
    }

    public function beginRename(int $tagId): void
    {
        $tag = RecruitmentTag::query()->findOrFail($tagId);
        Gate::authorize('update', $tag);

        $this->resetErrorBag();
        $this->renamingId = $tag->getKey();
        $this->renameName = $tag->name;
        $this->renameColor = $tag->color ?? 'neutral';
    }

    public function rename(RecruitmentTagService $tags): void
    {
        abort_if($this->renamingId === null, 404);

        $tag = RecruitmentTag::query()->findOrFail($this->renamingId);
        Gate::authorize('update', $tag);

        if ($this->run(fn () => $tags->rename(auth()->user(), $tag, $this->renameName, $this->renameColor), 'renameName')) {
            $this->cancelRename();
        }
    }

    public function cancelRename(): void
    {
        $this->reset('renamingId', 'renameName', 'renameColor');
        $this->resetErrorBag();
    }

    public function delete(RecruitmentTagService $tags, int $tagId): void
    {
        $tag = RecruitmentTag::query()->findOrFail($tagId);
        Gate::authorize('delete', $tag);

        $this->run(fn () => $tags->delete(auth()->user(), $tag));
    }

    public function restore(RecruitmentTagService $tags, int $tagId): void
    {
        $tag = RecruitmentTag::onlyTrashed()->findOrFail($tagId);
        Gate::authorize('restore', $tag);

        $this->run(fn () => $tags->restore(auth()->user(), $tag));
    }

    public function render()
    {
        $user = auth()->user();
        $mayManage = Gate::allows('create', RecruitmentTag::class);

        return view('livewire.recruitment.applicants.tag-manager', [
            'mayManage' => $mayManage,
            'tags' => $mayManage
                ? RecruitmentTag::withTrashed()
                    ->withCount(['applications as visible_count' => fn (Builder $applications) => $applications
                        ->whereIn('recruitment_applications.id', RecruitmentApplication::query()->visibleTo($user)->select('recruitment_applications.id'))])
                    ->orderByRaw('deleted_at IS NOT NULL')
                    ->orderBy('name')
                    ->get()
                : collect(),
            'palette' => array_keys(config('recruitment.tags.palette', [])),
        ]);
    }

    /** Validation errors from the service land on the field they concern. */
    private function run(callable $action, string $nameField = 'tagName'): bool
    {
        $this->resetErrorBag();

        try {
            $action();
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                $this->addError($field === 'tagName' ? $nameField : $field, $messages[0]);
            }

            return false;
        }

        $this->dispatch('recruitment-tags-changed');

        return true;
    }
}
