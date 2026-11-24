<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The one visual vocabulary every status-like enum maps onto (W-28).
 *
 * A status enum implements HasStatusTone and answers tone() with one of
 * these; the badge, pill and board/calendar dots then take their colours
 * from here instead of each keeping a per-enum colour map. The classes use
 * the status tokens in resources/css/tokens.css, which carry their own
 * `.dark` values, so one class list reads correctly in both modes.
 *
 * Brand palette only (D1): Critical is the one red; the other four are grey
 * steps told apart by shape (solid fill, outline, soft fill, dashed outline)
 * and by the icon and label every badge carries, never by hue.
 *
 * Tailwind scans this file through an `@source` line in app.css: a class
 * written here is compiled even when no view repeats it.
 */
enum StatusTone: string
{
    /** Finished or accepted: done, reached, approved, validated, green. Solid ink fill. */
    case Good = 'good';

    /** Needs attention but is not failed: pending, on hold, postponed, amber. Soft grey fill. */
    case Warn = 'warn';

    /** Failed, refused or overdue: rejected, failed, denied, red, high priority. Red fill, white text. */
    case Critical = 'critical';

    /** In flight: in progress, ongoing, submitted, delivered, active. Ink outline. */
    case Active = 'active';

    /** Not started or out of play: todo, draft, planned, cancelled, low. Dashed faint outline. */
    case Neutral = 'neutral';

    /**
     * The badge: each tone has its own shape so the five read apart in greyscale.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Good => 'border-status-good bg-status-good text-white dark:text-surface-0',
            self::Warn => 'border-status-warn/40 bg-status-warn-soft text-status-warn',
            self::Critical => 'border-status-critical bg-status-critical text-white',
            self::Active => 'border-status-active bg-status-active-soft text-status-active',
            self::Neutral => 'border-dashed border-secondary-light bg-transparent text-secondary-medium dark:border-ink-faint dark:text-ink-muted',
        };
    }

    /**
     * Solid marker: the dot or stripe beside a group, column or calendar entry.
     * Active is a ring and Neutral a faint dot, so the marker shape follows the badge.
     */
    public function dotClass(): string
    {
        return match ($this) {
            self::Good => 'bg-status-good',
            self::Warn => 'bg-status-warn',
            self::Critical => 'bg-status-critical',
            self::Active => 'bg-transparent ring-[1.5px] ring-inset ring-status-active',
            self::Neutral => 'bg-secondary-light dark:bg-ink-faint',
        };
    }

    /**
     * The fallback icon (flux:icon name) for a badge whose case has none of its own.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Good => 'check-circle',
            self::Warn => 'clock',
            self::Critical => 'exclamation-triangle',
            self::Active => 'play-circle',
            self::Neutral => 'minus-circle',
        };
    }
}
