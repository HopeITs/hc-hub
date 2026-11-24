<?php

declare(strict_types=1);

namespace Modules\Tracking\Policies;

use Modules\Core\Models\User;
use Modules\Tracking\Models\ReportTemplatePlacement;

class ReportTemplatePlacementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('report-templates.manage');
    }

    public function view(User $user, ReportTemplatePlacement $placement): bool
    {
        return $this->manage($user, $placement);
    }

    public function manage(User $user, ReportTemplatePlacement $placement): bool
    {
        return ReportTemplatePlacement::query()->manageableBy($user)->whereKey($placement->id)->exists();
    }
}
