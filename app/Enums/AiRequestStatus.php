<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * How one call to the AI provider ended, stored in `ai_requests.status`.
 */
enum AiRequestStatus: string
{
    case Succeeded = 'succeeded';
    case Failed = 'failed';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
