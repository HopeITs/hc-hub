<?php

declare(strict_types=1);

namespace Modules\Hr\Enums;

/**
 * The state of one workflow run.

 * There is no Blocked value. It was dropped (D17) because it added nothing over
 * "the step has not been decided yet": a step with no assigned approver simply
 * stays active and the request stays under review.
 */
enum WorkflowInstanceStatus: int
{
    case Running = 0;

    case Completed = 1;

    case Rejected = 2;

    case Cancelled = 3;

    /**
     * @return array<string, mixed>
     */
    public static function getLocalizedName(int $value): array
    {
        $val = match ($value) {
            self::Completed->value => ['text' => ['en' => 'Completed', 'ar' => 'مكتمل'], 'color' => 'primary'],
            self::Rejected->value => ['text' => ['en' => 'Rejected', 'ar' => 'مرفوض'], 'color' => 'secondary-medium'],
            self::Cancelled->value => ['text' => ['en' => 'Cancelled', 'ar' => 'ملغى'], 'color' => 'secondary-light'],
            default => ['text' => ['en' => 'Running', 'ar' => 'جارٍ'], 'color' => 'primary'],
        };

        return [
            'value' => $val['text'][app()->getLocale()],
            'color' => $val['color'],
        ];
    }
}
