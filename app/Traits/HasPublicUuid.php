<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * A stable public `uuid` beside the integer primary key (Core_DB readiness,
 * plan 2b C2).
 *
 * The shared Core_DB identifies a row across databases by this value, so it
 * is filled once, on `creating`, and never changes afterwards: an update that
 * tries to replace a uuid already stored puts the stored one back. It is
 * deliberately not fillable; an importer that must keep a uuid from another
 * system sets it with forceFill() before the first save.
 *
 * UUIDv7 rather than v4: v7 starts with a millisecond timestamp, so new values
 * land at the end of the unique index instead of at random pages (cheaper
 * InnoDB inserts, the same reason the Core prefers sortable keys), and the
 * remaining 74 random bits keep them unique without coordination. The
 * creation time it reveals is not a secret for master data.
 *
 * The primary key and the route key stay the integer `id`.
 *
 * @mixin Model
 */
trait HasPublicUuid
{
    public static function bootHasPublicUuid(): void
    {
        static::creating(function (Model $model): void {
            if (blank($model->getAttribute('uuid'))) {
                $model->setAttribute('uuid', (string) Str::uuid7());
            }
        });

        static::updating(function (Model $model): void {
            $stored = $model->getOriginal('uuid');

            if (filled($stored)) {
                $model->setAttribute('uuid', $stored);
            } elseif (blank($model->getAttribute('uuid'))) {
                $model->setAttribute('uuid', (string) Str::uuid7());
            }
        });
    }
}
