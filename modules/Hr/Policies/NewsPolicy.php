<?php

declare(strict_types=1);

namespace Modules\Hr\Policies;

use Modules\Core\Models\User;
use Modules\Hr\Models\News;
use Modules\Hr\Support\Desk\HrViewer;
use Modules\Hr\Support\News\NewsViewer;

/**
 * Who may read and manage news (the old HR app's NewsAccess, on top of the
 * news.* permissions its routes asked for).
 *
 * HR, the general manager and administrators manage every item. A manager
 * may create news and manages only what they created. Everyone signed in
 * may read a released item addressed to them; an unreleased one (draft,
 * scheduled) is only for the people who manage it.
 */
class NewsPolicy
{
    /**
     * The management list (news.view).
     */
    /**
     * Administrators hold every news ability.
     */
    public function before(User $user): ?bool
    {
        return $user->isAdministrator() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('news.view');
    }

    public function view(User $user, News $news): bool
    {
        if ($this->manages($user, $news)) {
            return true;
        }

        return News::query()->whereKey($news->getKey())->released()->visibleTo(NewsViewer::targetsOf($user))->exists();
    }

    public function create(User $user): bool
    {
        return $user->can('news.create') && ($this->managesAll($user) || HrViewer::for($user)->isManager());
    }

    public function update(User $user, News $news): bool
    {
        return $user->can('news.update') && $this->manages($user, $news);
    }

    public function delete(User $user, News $news): bool
    {
        return $user->can('news.delete') && $this->manages($user, $news);
    }

    /**
     * Whether the user manages this item at all (sees its preview, state and audience).
     */
    public function manage(User $user, News $news): bool
    {
        return $this->manages($user, $news);
    }

    private function manages(User $user, News $news): bool
    {
        return $this->managesAll($user)
            || (HrViewer::for($user)->isManager() && (int) $news->created_by === (int) $user->id);
    }

    private function managesAll(User $user): bool
    {
        $viewer = HrViewer::for($user);

        return $viewer->isHr() || $viewer->seesOrganisation();
    }
}
