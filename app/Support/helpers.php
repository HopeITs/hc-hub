<?php

if (! function_exists('ar_digits')) {
    function ar_digits($value): string
    {
        $s = (string) ($value ?? '');

        return strtr($s, [
            '0' => '٠', '1' => '١', '2' => '٢', '3' => '٣', '4' => '٤',
            '5' => '٥', '6' => '٦', '7' => '٧', '8' => '٨', '9' => '٩',
        ]);
    }
}

if (! function_exists('en_digits')) {
    function en_digits($value): string
    {
        $s = (string) ($value ?? '');

        return strtr($s, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
    }
}

if (! function_exists('esc')) {
    function esc($value): string
    {
        return e($value ?? '');
    }
}
if (! function_exists('transColumn')) {
    function transColumn(string $base, ?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();

        return $base.'_'.$locale;
    }
}

if (! function_exists('formatDuration')) {
    function formatDuration($days, $hours)
    {
        // Accept decimal hours
        $days = (int) $days;

        // Split hours into whole hours + fractional minutes
        $wholeHours = (int) $hours;
        $minutes = (int) round(($hours - $wholeHours) * 60);

        // Handle 60 minutes → +1 hour
        if ($minutes >= 60) {
            $wholeHours += intdiv($minutes, 60);
            $minutes = $minutes % 60;
        }

        // Now normalize hours into days
        if ($wholeHours >= 24) {
            $days += intdiv($wholeHours, 24);
            $wholeHours = $wholeHours % 24;
        }

        $parts = [];

        if ($days > 0) {
            $parts[] = '<strong class="text-secondary-dark">'.$days.'</strong> '.
                trans_choice('general.day', $days);
        }

        if ($wholeHours > 0) {
            $parts[] = '<strong class="text-secondary-dark">'.$wholeHours.'</strong> '.
                trans_choice('general.hour', $wholeHours);
        }

        if ($minutes > 0) {
            $parts[] = '<strong class="text-secondary-dark">'.$minutes.'</strong> '.
                trans_choice('general.minute', $minutes);
        }

        return implode(' ', $parts) ?: '0 '.trans_choice('general.day', 2);
    }

}

if (! function_exists('contract_weekend_days')) {
    /**
     * Parse Center::weekends where Sunday is 0 and Saturday is 6.
     *
     * @return array<int, int>
     */
    function contract_weekend_days(mixed $weekends): array
    {
        return collect(is_array($weekends) ? $weekends : explode(',', (string) $weekends))
            ->map(fn ($day) => filter_var(trim((string) $day), FILTER_VALIDATE_INT))
            ->filter(fn ($day) => $day !== false && $day >= 0 && $day <= 6)
            ->unique()
            ->map(fn ($day) => (int) $day)
            ->values()
            ->all();
    }
}

if (! function_exists('contract_primary_weekend_day')) {
    function contract_primary_weekend_day(mixed $weekends): ?int
    {
        $days = contract_weekend_days($weekends);

        if (count($days) > 1 && in_array(6, $days, true)) {
            return 6;
        }

        return $days[0] ?? null;
    }
}

if (! function_exists('contract_project_type')) {
    function contract_project_type($contract): string
    {
        $program = $contract?->position?->program;
        $slug = strtoupper(trim((string) ($program?->slug ?? '')));
        $name = strtolower(trim((string) ($program?->name ?? '')));

        return match (true) {
            $slug === 'CU' => 'cu',
            in_array($slug, ['DISC', 'LEAP'], true) => 'disc_leap',
            $slug === 'SZ' || str_contains($name, 'study zone') || str_contains($name, 'studyzone') => 'studyzone',
            default => 'default',
        };
    }
}

if (! function_exists('orgCardTokens')) {
    /** Org-chart card size modifier (layout in app.css `.org-id-card`). */
    function orgCardTokens(bool $compact = false): array
    {
        return [
            'modifier' => $compact ? 'org-id-card--compact' : 'org-id-card--comfortable',
        ];
    }
}
