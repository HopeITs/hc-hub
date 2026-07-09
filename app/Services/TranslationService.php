<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TranslationService
{
    public function arabicToEnglish(string $text): ?string
    {
        $text = trim($text);

        if ($text === '') {
            return null;
        }

        $response = Http::timeout(8)
            ->retry(1, 300, throw: false)
            ->get('https://translate.googleapis.com/translate_a/single', [
                'client' => 'gtx',
                'sl' => 'ar',
                'tl' => 'en',
                'dt' => 't',
                'q' => $text,
            ]);

        if (! $response->successful()) {
            return null;
        }

        $segments = $response->json('0');

        if (! is_array($segments)) {
            return null;
        }

        $translated = collect($segments)
            ->map(fn ($segment) => is_array($segment) ? ($segment[0] ?? '') : '')
            ->implode('');

        return trim($translated) !== '' ? trim($translated) : null;
    }
}
