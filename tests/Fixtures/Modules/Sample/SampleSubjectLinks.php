<?php

declare(strict_types=1);

namespace Tests\Fixtures\Modules\Sample;

use App\Support\Modules\Contracts\SubjectLinkSource;

final class SampleSubjectLinks implements SubjectLinkSource
{
    public const string Type = 'sample_item';

    public function handles(string $type): bool
    {
        return $type === self::Type;
    }

    public function routeFor(string $type, int $id): ?string
    {
        return route('sample.index', ['item' => $id]);
    }
}
