<?php

namespace App\Exceptions;

use RuntimeException;

class UnsafeRecruitmentFileException extends RuntimeException
{
    public static function make(string $reason): self
    {
        return new self(trans("validation.recruitment_file.{$reason}"));
    }
}
