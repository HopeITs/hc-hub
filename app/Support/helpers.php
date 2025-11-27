<?php

if (! function_exists('ar_digits')) {
    function ar_digits($value): string
    {
        $s = (string) ($value ?? '');
        return strtr($s, [
            '0'=>'٠','1'=>'١','2'=>'٢','3'=>'٣','4'=>'٤',
            '5'=>'٥','6'=>'٦','7'=>'٧','8'=>'٨','9'=>'٩',
        ]);
    }
}

if (! function_exists('en_digits')) {
    function en_digits($value): string
    {
        $s = (string) ($value ?? '');
        return strtr($s, [
            '٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4',
            '٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9',
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
    function transColumn(string $base, ?string $locale = null): string {
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
