<?php

declare(strict_types=1);

namespace Modules\Hr\Enums;

/** How a termination reason groups in the "Why people leave" report. */
enum LeavingCategory: string
{
    case Voluntary = 'voluntary';
    case Involuntary = 'involuntary';
    case NaturalEnd = 'natural_end';
    case Other = 'other';

    public function label(): string
    {
        return __('hr_reports.category.'.$this->value);
    }

    /**
     * @return array<string, string> value => label, for a select
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
