<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\User;

/**
 * One person's favourites, recents and phone bar (NavigationMemory). The
 * stored lists are only ever read through the active role's menu and the
 * directory scope, so a row never opens a page or a profile on its own.
 *
 * @property int $id
 * @property int $user_id
 * @property array<mixed>|null $favourites
 * @property array<mixed>|null $recent_pages
 * @property array<mixed>|null $recent_people
 * @property array<mixed>|null $bottom_bar
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['user_id', 'favourites', 'recent_pages', 'recent_people', 'bottom_bar'])]
class NavigationPreference extends Model
{
    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'favourites' => 'array',
            'recent_pages' => 'array',
            'recent_people' => 'array',
            'bottom_bar' => 'array',
        ];
    }
}
