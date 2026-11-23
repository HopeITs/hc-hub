<?php

declare(strict_types=1);

namespace Tests\Fixtures\Modules\Sample;

use App\Support\Modules\Contracts\EmployeeTabSource;
use Modules\Core\Models\User;

final class SampleEmployeeTab implements EmployeeTabSource
{
    public function key(): string
    {
        return 'sample';
    }

    public function label(): string
    {
        return 'Sample tab';
    }

    public function visibleTo(User $viewer, int $employeeId): bool
    {
        return $viewer->can(SampleServiceProvider::Ability);
    }

    public function component(): string
    {
        return 'sample::employees.tab';
    }
}
