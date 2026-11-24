<?php

declare(strict_types=1);

namespace Tests\Fixtures\Modules\Sample;

use App\Support\Modules\Contracts\PickerScopeSource;
use Modules\Core\Models\User;

final class SamplePickerScopes implements PickerScopeSource
{
    public const string Scope = 'sample.items';

    public function handles(string $scope): bool
    {
        return $scope === self::Scope;
    }

    public function parameters(string $scope): array
    {
        return [];
    }

    public function options(string $scope, User $user, array $parameters): ?array
    {
        return [['value' => '1', 'label' => 'Sample item']];
    }

    public function labels(string $scope, array $values, array $parameters): array
    {
        return in_array('1', $values, true) ? ['1' => 'Sample item'] : [];
    }
}
