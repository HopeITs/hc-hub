<?php

declare(strict_types=1);

namespace Modules\Hr\Livewire\Concerns\Recruitment;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Modules\Core\Models\User;
use Modules\Hr\Actions\Recruitment\RecruitmentApplicationParticipantService;
use Modules\Hr\Actions\Recruitment\RecruitmentApplicationStatusService;
use Modules\Hr\Actions\Recruitment\RecruitmentTagService;
use Modules\Hr\Models\RecruitmentApplication;
use Modules\Hr\Models\RecruitmentApplicationParticipant;
use Modules\Hr\Models\RecruitmentTag;
use Modules\Hr\Support\Recruitment\RecruitmentConfig;

/**
 * The applicants list's bulk actions (tag, reject, grant profile access) and
 * the shared tag list. The selected ids come from the browser: each service
 * re-scopes every id before it writes, and each action is authorized by its
 * own policy ability.
 */
trait ManagesApplicantBulk
{
    /** @var list<string> application ids, from the browser; re-scoped by every service */
    public array $selected = [];

    /** The bulk action whose dialog is open: tags, reject or access ('' when none). */
    public string $bulk = '';

    public bool $showBulk = false;

    public string $bulkMode = 'attach';

    public string $bulkTagId = '';

    public string $bulkReasonCode = '';

    public string $bulkReason = '';

    public bool $bulkNotify = true;

    public string $bulkUserId = '';

    public string $bulkRole = RecruitmentApplicationParticipant::ROLE_REVIEWER;

    /** @var list<string> the outcome lines of the last bulk action */
    public array $bulkResult = [];

    public bool $showTags = false;

    public string $tagName = '';

    public string $tagColor = 'neutral';

    public string $tagKind = RecruitmentTag::KIND_NEUTRAL;

    #[Locked]
    public ?int $renamingId = null;

    public string $renameName = '';

    public string $renameColor = 'neutral';

    public string $renameKind = RecruitmentTag::KIND_NEUTRAL;

    public function selectMatching(): void
    {
        $this->selected = $this->matchingIds(app(RecruitmentTagService::class)->bulkLimit());
    }

    public function clearSelection(): void
    {
        $this->selected = [];
    }

    public function openBulk(string $action): void
    {
        $ability = match ($action) {
            'tags' => 'bulkTag',
            'reject' => 'bulkReject',
            'access' => 'bulkManageReviewParticipants',
            default => abort(403),
        };
        Gate::authorize($ability, RecruitmentApplication::class);

        $this->resetErrorBag();
        $this->reset('bulkMode', 'bulkTagId', 'bulkReasonCode', 'bulkReason', 'bulkNotify', 'bulkUserId', 'bulkRole', 'bulkResult');
        $this->bulk = $action;
        $this->showBulk = true;
    }

    public function applyBulk(RecruitmentTagService $tags, RecruitmentApplicationStatusService $statuses, RecruitmentApplicationParticipantService $participants): void
    {
        $this->resetErrorBag();
        $ids = $this->selectedIds();

        try {
            $this->bulkResult = match ($this->bulk) {
                'tags' => $this->bulkTags($tags, $ids),
                'reject' => $this->bulkReject($statuses, $ids),
                'access' => $this->bulkAccess($participants, $ids),
                default => abort(403),
            };
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                $this->addError(match ($field) {
                    'tagId' => 'bulkTagId',
                    'mode' => 'bulkMode',
                    'reasonCode' => 'bulkReasonCode',
                    'reason' => 'bulkReason',
                    'userId', 'bulkParticipantId' => 'bulkUserId',
                    'role', 'bulkParticipantRole' => 'bulkRole',
                    default => 'selected',
                }, $messages[0]);
            }

            return;
        }

        $this->showBulk = false;
        $this->bulk = '';
        $this->selected = [];
        $this->clearRows();
    }

    public function dismissBulkResult(): void
    {
        $this->bulkResult = [];
    }

    public function createTag(RecruitmentTagService $tags): void
    {
        Gate::authorize('create', RecruitmentTag::class);

        if ($this->tagCall(fn () => $tags->create($this->viewer(), $this->tagName, $this->tagColor, $this->tagKind))) {
            $this->reset('tagName', 'tagColor', 'tagKind');
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
        $this->renameKind = $tag->kind ?? RecruitmentTag::KIND_NEUTRAL;
    }

    public function renameTag(RecruitmentTagService $tags): void
    {
        abort_if($this->renamingId === null, 404);
        $tag = RecruitmentTag::query()->findOrFail($this->renamingId);
        Gate::authorize('update', $tag);

        if ($this->tagCall(fn () => $tags->rename($this->viewer(), $tag, $this->renameName, $this->renameColor, $this->renameKind), 'renameName', 'renameColor', 'renameKind')) {
            $this->cancelRename();
        }
    }

    public function cancelRename(): void
    {
        $this->reset('renamingId', 'renameName', 'renameColor', 'renameKind');
        $this->resetErrorBag();
    }

    public function deleteTag(int $tagId, RecruitmentTagService $tags): void
    {
        $tag = RecruitmentTag::query()->findOrFail($tagId);
        Gate::authorize('delete', $tag);
        $this->tagCall(fn () => $tags->delete($this->viewer(), $tag));
    }

    public function restoreTag(int $tagId, RecruitmentTagService $tags): void
    {
        $tag = RecruitmentTag::onlyTrashed()->findOrFail($tagId);
        Gate::authorize('restore', $tag);
        $this->tagCall(fn () => $tags->restore($this->viewer(), $tag));
    }

    /** The shared tags with how often each is used inside the viewer's scope (manage-tags only). */
    #[Computed]
    public function managedTags(): Collection
    {
        if (! $this->showTags || ! Gate::allows('create', RecruitmentTag::class)) {
            return collect();
        }

        $visible = RecruitmentApplication::query()->visibleTo($this->viewer())->select('recruitment_applications.id');

        return RecruitmentTag::withTrashed()
            ->withCount(['applications as visible_count' => fn (Builder $applications) => $applications->whereIn('recruitment_applications.id', $visible)])
            ->orderByRaw('deleted_at IS NOT NULL')
            ->orderBy('name')
            ->get();
    }

    /** @return array<int, string> user id => "name — email", for the profile-access picker */
    #[Computed]
    public function activeStaff(): array
    {
        if ($this->bulk !== 'access') {
            return [];
        }

        $directory = User::directory();

        return User::query()
            ->whereHas('employee', fn (Builder $employee): Builder => $employee->where('is_active', true))
            ->pluck('id')
            ->mapWithKeys(fn ($id): array => [(int) $id => ($directory[(int) $id]['name'] ?? '#'.$id).' — '.($directory[(int) $id]['email'] ?? '')])
            ->sort()
            ->all();
    }

    /** @return list<string> */
    public function tagPalette(): array
    {
        return array_keys((array) RecruitmentConfig::get('recruitment.tags.palette', []));
    }

    /** @return list<int> */
    private function selectedIds(): array
    {
        return collect($this->selected)
            ->filter(fn ($id): bool => is_scalar($id) && filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false)
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /** @param list<int> $ids @return list<string> */
    private function bulkTags(RecruitmentTagService $tags, array $ids): array
    {
        Gate::authorize('bulkTag', RecruitmentApplication::class);

        if (! in_array($this->bulkMode, ['attach', 'detach'], true)) {
            throw ValidationException::withMessages(['mode' => __('hr_recruitment.applicant_ui.tags.bulk_mode_invalid')]);
        }

        $outcome = $this->bulkMode === 'attach'
            ? $tags->bulkAttach($this->viewer(), $ids, $this->bulkTagId)
            : $tags->bulkDetach($this->viewer(), $ids, $this->bulkTagId);

        return [__('hr_recruitment.applicant_ui.tags.bulk_'.$this->bulkMode.'_done', $outcome)];
    }

    /** @param list<int> $ids @return list<string> */
    private function bulkReject(RecruitmentApplicationStatusService $statuses, array $ids): array
    {
        Gate::authorize('bulkReject', RecruitmentApplication::class);

        $result = $statuses->bulkReject($this->viewer(), $ids, $this->bulkReasonCode, $this->bulkReason, $this->bulkNotify);
        $lines = [__('hr_recruitment.applicant_ui.decisions.bulk_done', ['count' => $result['rejected'] ?? 0])];

        foreach (['already_rejected', 'ineligible', 'out_of_scope', 'queued', 'already_notified', 'no_recipient', 'queue_failed'] as $key) {
            if (($result[$key] ?? 0) > 0) {
                $lines[] = __('hr_recruitment.applicant_ui.decisions.bulk_outcome.'.$key, ['count' => $result[$key]]);
            }
        }

        return $lines;
    }

    /** @param list<int> $ids @return list<string> */
    private function bulkAccess(RecruitmentApplicationParticipantService $participants, array $ids): array
    {
        Gate::authorize('bulkManageReviewParticipants', RecruitmentApplication::class);

        return [__('hr_recruitment.applicant_ui.profile_reviews.bulk_done', $participants->bulkAssign($this->viewer(), $ids, $this->bulkUserId, $this->bulkRole))];
    }

    /** Runs a tag-list write, putting a refusal on the field it concerns. */
    private function tagCall(callable $action, string $nameField = 'tagName', string $colorField = 'tagColor', string $kindField = 'tagKind'): bool
    {
        $this->resetErrorBag();

        try {
            $action();
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                $this->addError(match ($field) {
                    'tagName' => $nameField,
                    'tagColor' => $colorField,
                    'tagKind' => $kindField,
                    default => $nameField,
                }, $messages[0]);
            }

            return false;
        }

        unset($this->managedTags);
        $this->clearRows();

        return true;
    }

    private function clearRows(): void
    {
        unset($this->rows, $this->indicatorSets, $this->schedulable, $this->stageCounts, $this->campaignLens, $this->options);
    }
}
