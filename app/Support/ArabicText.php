<?php

// namespace App\Support;

// use ArPHP\I18N\Arabic;

// class ArabicText
// {
//     protected Arabic $ar;

//     public function __construct()
//     {
//         $this->ar = new Arabic();
//     }

//     public static function glyphs(string $text): string
//     {
//         return (new static())->ar->utf8Glyphs($text);
//     }

//     public static function words(int|float $n): string
//     {
//         $s = (new static())->ar->int2str((int)$n);
//         return self::glyphs($s);
//     }

//     public static function date(string $format, ?int $timestamp = null): string
//     {
//         $ts = $timestamp ?? time();
//         $s  = (new static())->ar->date($format, $ts);
//         return self::glyphs($s);
//     }

//     public static function digits(string $text): string
//     {
//         return (new static())->ar->en2ar($text);
//     }
// }

namespace App\Support;

use ArPHP\I18N\Arabic;

class ArabicText
{
    protected Arabic $ar;

    public function __construct()
    {
        $this->ar = new Arabic;
    }

    /**
     * Standard glyph shaper for plain text.
     */
    public static function glyphs(string $text): string
    {
        // Limit set to 2000 to prevent internal wrapping, false protects English numbers
        return (new static)->ar->utf8Glyphs($text, 2000, false);
    }

    /**
     * Smart HTML-aware RTL shaper.
     */
    public static function smartGlyphs(?string $text, int $limit = 2000): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        // Fast-path: If there are no HTML tags, use standard processing
        if ($text === strip_tags($text)) {
            return (new static)->ar->utf8Glyphs($text, $limit, false);
        }

        // 1. Split text based on <br> tags to maintain vertical line flow
        $lines = preg_split('/(<br\s*\/?>)/i', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        $processedLines = [];

        foreach ($lines as $line) {
            // Keep <br> tags exactly as they are
            if (preg_match('/^<br\s*\/?>$/i', $line)) {
                $processedLines[] = $line;

                continue;
            }

            if ($line === '') {
                $processedLines[] = '';

                continue;
            }

            // 2. Split the line into HTML tags and plain text segments
            $parts = preg_split('/(<[^>]+>)/u', $line, -1, PREG_SPLIT_DELIM_CAPTURE);

            $textSegments = [];
            foreach ($parts as $index => $part) {
                if (strpos($part, '<') !== 0 && $part !== '') {
                    $textSegments[] = [
                        'index' => $index,
                        'text' => $part,
                        'length' => mb_strlen($part, 'UTF-8'),
                    ];
                }
            }

            // If the line has no text (only HTML), keep it as is
            if (empty($textSegments)) {
                $processedLines[] = $line;

                continue;
            }

            // 3. Assemble all plain text to shape it in correct contextual Bidi flow
            $plainText = '';
            foreach ($textSegments as $seg) {
                $plainText .= $seg['text'];
            }

            // 4. Shape the text
            // THE FIX: High limit prevents '\n' injection disrupting mb_substr offsets.
            // false parameter native protects English digits.
            $reversedPlainText = (new static)->ar->utf8Glyphs($plainText, $limit, false);

            // 5. Slice the shaped text back into the original array segments
            $offset = 0;
            $reversedSlices = [];
            for ($i = count($textSegments) - 1; $i >= 0; $i--) {
                $len = $textSegments[$i]['length'];
                $slice = mb_substr($reversedPlainText, $offset, $len, 'UTF-8');
                $reversedSlices[$textSegments[$i]['index']] = $slice;
                $offset += $len;
            }

            // Replace plain text parts with their reversed/shaped counterparts
            foreach ($reversedSlices as $index => $slice) {
                $parts[$index] = $slice;
            }

            // 6. Map opening and closing HTML tags
            $openTagsStack = [];
            $pairs = [];
            foreach ($parts as $index => $token) {
                if (strpos($token, '<') === 0) {
                    if (strpos($token, '</') === 0) {
                        if (! empty($openTagsStack)) {
                            $openIndex = array_pop($openTagsStack);
                            $pairs[] = [$openIndex, $index];
                        }
                    } else {
                        // Ignore self-closing tags
                        if (substr($token, -2) !== '/>' && ! preg_match('/<input|<img|<hr/i', $token)) {
                            $openTagsStack[] = $index;
                        }
                    }
                }
            }

            // 7. Reverse the array to achieve Left-to-Right structural compliance for DOMPDF
            $reversedParts = array_reverse($parts);
            $count = count($parts);

            // 8. Re-align paired HTML tags so opening tags stay in front of closing tags
            foreach ($pairs as $pair) {
                $oldOpen = $pair[0];
                $oldClose = $pair[1];

                $newOpenPos = $count - 1 - $oldClose;
                $newClosePos = $count - 1 - $oldOpen;

                $tmp = $reversedParts[$newOpenPos];
                $reversedParts[$newOpenPos] = $reversedParts[$newClosePos];
                $reversedParts[$newClosePos] = $tmp;
            }

            $processedLines[] = implode('', $reversedParts);
        }

        return implode('', $processedLines);
    }

    /**
     * Helper to render Arabic numbers as words.
     */
    public static function words(int|float $n): string
    {
        $s = (new static)->ar->int2str((int) $n);

        return self::glyphs($s);
    }

    /**
     * Helper to render dates safely formatted.
     */
    public static function date(string $format, ?int $timestamp = null): string
    {
        $ts = $timestamp ?? time();
        $s = (new static)->ar->date($format, $ts);

        return self::glyphs($s);
    }

    /**
     * Helper to explicitly force English digits into Arabic (٠-٩).
     */
    public static function digits(string $text): string
    {
        return (new static)->ar->en2ar($text);
    }
}
