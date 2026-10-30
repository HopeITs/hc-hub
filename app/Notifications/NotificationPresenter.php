<?php

namespace App\Notifications;

use Illuminate\Support\Carbon;

/**
 * Renders stored notification data in a given language. Two shapes are
 * stored and both render here:
 *
 * - tracking style: `key` + `params` (+ `count` when grouping merged rows),
 *   translated at display time;
 * - HR style: `title` and `body` already written per language (`{en, ar}`).
 *
 * Module icons and labels come from config('notifications.inbox_modules').
 */
class NotificationPresenter
{
    /** The interface languages search text is written in. */
    public const LOCALES = ['en', 'ar'];

    /**
     * The headline of a notification.
     *
     * @param  array<string, mixed>  $data
     */
    public function title(array $data, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        if (isset($data['key']) && is_string($data['key']) && $data['key'] !== '') {
            return $this->sentence($data, $locale);
        }

        $title = $this->localizedText($data['title'] ?? null, $locale);

        return $title !== '' ? $title : (string) __('notifications.untitled', [], $locale);
    }

    /**
     * The second line of an HR-style notification; null for a keyed one,
     * whose title is the whole sentence.
     *
     * @param  array<string, mixed>  $data
     */
    public function body(array $data, ?string $locale = null): ?string
    {
        $body = $this->localizedText($data['body'] ?? null, $locale ?? app()->getLocale());

        return $body === '' ? null : $body;
    }

    /**
     * A keyed notification as one sentence, "+ N more updates" when merged.
     *
     * @param  array<string, mixed>  $data
     */
    public function sentence(array $data, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $params = [];

        foreach ((array) ($data['params'] ?? []) as $name => $param) {
            $params[(string) $name] = $this->param($param, $locale);
        }

        $sentence = (string) __((string) ($data['key'] ?? ''), $params, $locale);
        $more = (int) ($data['count'] ?? 1) - 1;

        if ($more > 0) {
            $sentence .= ' '.trans_choice('notifications.more_updates', $more, ['count' => $more], $locale);
        }

        return $sentence;
    }

    /**
     * The text in every interface language, one line each: what inbox search
     * matches, so people find the words they actually read.
     *
     * @param  array<string, mixed>  $data
     */
    public function searchText(array $data): string
    {
        $lines = [];

        foreach (self::LOCALES as $locale) {
            $lines[] = $this->title([...$data, 'count' => 1], $locale);
            $lines[] = $this->body($data, $locale);
        }

        return implode("\n", array_filter($lines, fn (?string $line): bool => $line !== null && $line !== ''));
    }

    /**
     * The Heroicon a module is drawn with.
     */
    public function moduleIcon(string $module): string
    {
        return (string) (config("notifications.inbox_modules.{$module}.icon") ?? 'bell');
    }

    public function moduleLabel(string $module, ?string $locale = null): string
    {
        $key = config("notifications.inbox_modules.{$module}.label");

        return is_string($key) ? (string) __($key, [], $locale) : $module;
    }

    /**
     * One typed param as display text.
     */
    public function param(mixed $param, string $locale): string
    {
        if (! is_array($param)) {
            return is_scalar($param) ? (string) $param : '';
        }

        return match (true) {
            isset($param['trans']) => (string) __((string) $param['trans'], array_map(fn (mixed $nested): string => $this->param($nested, $locale), (array) ($param['params'] ?? [])), $locale),
            isset($param['date']) => $this->formatDate($param['date'], (string) __('notifications.formats.date', [], $locale), $locale),
            isset($param['datetime']) => $this->formatDate($param['datetime'], (string) __('notifications.formats.datetime', [], $locale), $locale),
            isset($param['localized']) => $this->localizedText($param['localized'], $locale),
            isset($param['localized_list']) => implode(
                $locale === 'ar' ? '، ' : ', ',
                array_map(fn (mixed $names): string => $this->localizedText($names, $locale), (array) $param['localized_list']),
            ),
            default => '',
        };
    }

    /**
     * A `{en, ar}` pair in the given language, English when that one is empty.
     */
    private function localizedText(mixed $names, string $locale): string
    {
        if (is_string($names)) {
            return $names;
        }

        if (! is_array($names)) {
            return '';
        }

        $preferred = $names[$locale] ?? '';

        return is_string($preferred) && $preferred !== '' ? $preferred : (string) ($names['en'] ?? '');
    }

    private function formatDate(mixed $date, string $format, string $locale): string
    {
        // locale() as a setter changes the date in place; its return type is too wide to chain on.
        $parsed = Carbon::parse(is_string($date) ? $date : null);
        $parsed->locale($locale);

        return $parsed->translatedFormat($format);
    }
}
