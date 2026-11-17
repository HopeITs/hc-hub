<?php

declare(strict_types=1);

namespace Modules\Tracking\Support;

use App\Support\Modules\Contracts\EmployeeTabSource;
use Modules\Core\Models\User;

/**
 * The "Projects" tab on an employee's profile (roadmap 2.7): the projects
 * the person is on now and was on before, read from project_members. The
 * person sees their own; anyone who can see projects sees everyone's.
 */
final class EmployeeProjectsTab implements EmployeeTabSource
{
    public function key(): string
    {
        return 'projects';
    }

    public function label(): string
    {
        return __('projects.employee_projects.tab');
    }

    public function visibleTo(User $viewer, int $employeeId): bool
    {
        return (int) $viewer->employee_id === $employeeId || $viewer->can('projects.view');
    }

    public function component(): string
    {
        return 'projects.employee-projects';
    }
}
