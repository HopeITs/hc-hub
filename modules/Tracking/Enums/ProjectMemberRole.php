<?php

declare(strict_types=1);

namespace Modules\Tracking\Enums;

use App\Enums\Concerns\HasTranslatedLabel;

/**
 * The part someone plays on a project team (D4). A role describes the
 * participation only; it grants no permission. Owner mirrors
 * projects.owner_employee_id and is kept in step by the project itself, so it
 * is never added or ended by hand.
 *
 * Values are lower case and stored in `project_members.role` (VARCHAR(30)).
 */
enum ProjectMemberRole: string
{
    use HasTranslatedLabel;

    case Owner = 'owner';
    case Coordinator = 'coordinator';
    case Member = 'member';
    case MealOfficer = 'meal_officer';
    case FinanceOfficer = 'finance_officer';
    case Trainer = 'trainer';
    case Consultant = 'consultant';
    case Volunteer = 'volunteer';

    /**
     * All role values, for validation rules.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * The roles a project manager picks from: every role but Owner.
     *
     * @return list<self>
     */
    public static function assignable(): array
    {
        return array_values(array_filter(self::cases(), fn (self $role): bool => $role !== self::Owner));
    }
}
