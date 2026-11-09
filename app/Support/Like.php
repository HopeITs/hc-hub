<?php

declare(strict_types=1);

namespace App\Support;

use Closure;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Builds LIKE patterns from text a user typed.
 *
 * Binding already keeps a search term out of the SQL, but inside the pattern
 * `%` and `_` still act as wildcards, so a bare `%` matches every row and
 * `_` matches any one character. Every search box goes through here so the
 * term is matched literally, and is never longer than MaxTermLength: a search
 * box accepts as much text as a Livewire request may carry, and a pattern
 * that long makes the database scan every row for it.
 */
final class Like
{
    /**
     * Characters of a search term that are used; the rest is ignored.
     */
    public const MaxTermLength = 100;

    /**
     * The part of what was typed that is searched for: trimmed, and cut to
     * MaxTermLength.
     */
    public static function term(string $term): string
    {
        return mb_substr(trim($term), 0, self::MaxTermLength);
    }

    /**
     * Escape the LIKE metacharacters (and the escape character itself) so the
     * term matches literally. MySQL uses backslash as its default escape.
     */
    public static function escape(string $term): string
    {
        return addcslashes($term, '%_\\');
    }

    /**
     * A pattern matching rows that contain the (trimmed, capped) term anywhere.
     */
    public static function contains(string $term): string
    {
        return '%'.self::escape(self::term($term)).'%';
    }

    /**
     * A where-closure for a search box: rows whose column holds every typed
     * word, in any order, both folded (ArabicText::fold: مدرسة finds مدرسه,
     * tashkeel ignored; a leading «ال» is optional, so «موسسه اغاثه» finds
     * «مؤسسة الإغاثة»). From three letters the term is also tried as read on
     * the other keyboard layout ("حقخلقشةس" also searches "programs"); a
     * wrong-layout reading of real text is gibberish, so it adds no rows of
     * its own. Pass it to where() or orWhere().
     *
     * @return Closure(EloquentBuilder<*>|QueryBuilder): void
     */
    public static function matches(string $column, string $search, bool $acrossLayouts = true): Closure
    {
        return self::everyWord(
            $search,
            fn (EloquentBuilder|QueryBuilder $query, string $word) => $query->where(self::word($column, $word)),
            $acrossLayouts,
        );
    }

    /**
     * A where-closure for a search box over several fields: every typed word
     * must be found, each in any one of them, so "UNICEF Syria" finds the
     * donor named UNICEF in Syria. $anyField adds, for one folded word, the
     * fields it may be in, joined with orWhere (use word() for a column).
     * Words are folded and «ال» is optional (ArabicText::searchWords); from
     * three letters the search is also read on the other keyboard layout.
     *
     * @param  Closure(EloquentBuilder<*>|QueryBuilder, string): mixed  $anyField
     * @return Closure(EloquentBuilder<*>|QueryBuilder): void
     */
    public static function everyWord(string $search, Closure $anyField, bool $acrossLayouts = true): Closure
    {
        $term = trim(self::term($search));
        $readings = $acrossLayouts ? KeyboardLayout::readings($term) : [$term];
        $wordSets = array_values(array_unique(array_map(
            fn (string $reading): string => implode(' ', ArabicText::searchWords($reading)),
            $readings,
        )));

        // One bracketed group, so the readings' orWhere never escapes into the
        // caller's own conditions (a whereHas() link, other filters).
        return fn (EloquentBuilder|QueryBuilder $query) => $query->where(function (EloquentBuilder|QueryBuilder $readings) use ($wordSets, $anyField): void {
            foreach ($wordSets as $words) {
                $readings->orWhere(function (EloquentBuilder|QueryBuilder $reading) use ($words, $anyField): void {
                    foreach ($words === '' ? [''] : explode(' ', $words) as $word) {
                        $reading->where(fn (EloquentBuilder|QueryBuilder $fields) => $anyField($fields, $word));
                    }
                });
            }
        });
    }

    /**
     * Every folded word everyWord() will look for, across all readings of the
     * search, so a caller can look words up ahead (ids in another database).
     *
     * @return list<string>
     */
    public static function searchWords(string $search, bool $acrossLayouts = true): array
    {
        $term = trim(self::term($search));
        $readings = $acrossLayouts ? KeyboardLayout::readings($term) : [$term];

        return array_values(array_unique(array_merge(...array_map(ArabicText::searchWords(...), $readings))));
    }

    /**
     * A where-closure: the column contains one already-folded search word.
     * An Arabic word compares the column folded too (ArabicText::foldSql);
     * any other word is a plain LIKE, which the collation already makes
     * case-insensitive, so English searches pay nothing for the folding.
     *
     * @return Closure(EloquentBuilder<*>|QueryBuilder): void
     */
    public static function word(string $column, string $word): Closure
    {
        return function (EloquentBuilder|QueryBuilder $query) use ($column, $word): void {
            $pattern = '%'.self::escape($word).'%';

            if (! KeyboardLayout::hasArabic($word)) {
                $query->where($column, 'like', $pattern);

                return;
            }

            $base = $query instanceof EloquentBuilder ? $query->getQuery() : $query;
            $query->whereRaw(ArabicText::foldSql($base->getGrammar()->wrap($column)).' like ?', [$pattern]);
        };
    }
}
