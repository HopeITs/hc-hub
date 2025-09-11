<?php

namespace App\Support;

use ArPHP\I18N\Arabic;

class ArabicText
{
    protected Arabic $ar;

    public function __construct()
    {
        $this->ar = new Arabic();
    }

    public static function glyphs(string $text): string
    {
        return (new static())->ar->utf8Glyphs($text);
    }

    public static function words(int|float $n): string
    {
        $s = (new static())->ar->int2str((int)$n);
        return self::glyphs($s);
    }

    public static function date(string $format, ?int $timestamp = null): string
    {
        $ts = $timestamp ?? time();
        $s  = (new static())->ar->date($format, $ts);
        return self::glyphs($s);
    }

    public static function digits(string $text): string
    {
        return (new static())->ar->en2ar($text);
    }
}
