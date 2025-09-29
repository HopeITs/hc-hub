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
