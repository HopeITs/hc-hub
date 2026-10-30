<?php

namespace App\Enums;

/**
 * How loudly a notification arrives: info (inbox only), important (named in
 * the row), critical (pinned at the top of the bell until read).
 *
 * Stored in `notifications.priority`. Values are lower case.
 */
enum NotificationPriority: string
{
    case Info = 'info';
    case Important = 'important';
    case Critical = 'critical';

    /**
     * The position of this priority, so two can be compared.
     */
    public function rank(): int
    {
        return match ($this) {
            self::Info => 0,
            self::Important => 1,
            self::Critical => 2,
        };
    }

    /**
     * The loudest of the given priorities, Info when none are given.
     */
    public static function highest(self ...$priorities): self
    {
        $highest = self::Info;

        foreach ($priorities as $priority) {
            if ($priority->rank() > $highest->rank()) {
                $highest = $priority;
            }
        }

        return $highest;
    }

    public function label(): string
    {
        return __('notifications.priorities.'.$this->value);
    }
}
