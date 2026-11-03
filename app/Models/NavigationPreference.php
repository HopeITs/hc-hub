<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One person's favourites, recents and phone bar (NavigationMemory). The
 * stored lists are only ever read through the active role's menu and the
 * directory scope, so a row never opens a page or a profile on its own.
 *
 * The lists are stored JSON and read defensively (NavigationMemory filters
 * every id), so they are typed as whatever an array may hold.
 *
 * @property array<mixed>|null $favourites
 * @property array<mixed>|null $recent_pages
 * @property array<mixed>|null $recent_people
 * @property array<mixed>|null $bottom_bar
 */
class NavigationPreference extends Model
{
    protected $fillable = ['user_id', 'favourites', 'recent_pages', 'recent_people', 'bottom_bar'];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return array<string, string> */
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
