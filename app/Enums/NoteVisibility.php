<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Who reads a note, stored in `notes.visibility`: only its author, or
 * whoever can view the record the note is about.
 */
enum NoteVisibility: string
{
    case Private = 'private';
    case Context = 'context';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
