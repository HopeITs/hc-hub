<?php

declare(strict_types=1);

namespace Modules\Tracking\Concerns;

/**
 * A model in the middle of an expand → contract column rename (roadmap 1.9):
 * the old and the new column both exist, the code reads the new one, and
 * every save copies whichever of a pair changed into the other so rows stay
 * equal until the old column is dropped. When both changed, the new one wins.
 *
 * Writes through the query builder fire no model events and must fill both
 * columns themselves.
 */
trait MirrorsRenamedColumns
{
    public static function bootMirrorsRenamedColumns(): void
    {
        static::saving(function (self $model): void {
            foreach ($model->renamedColumns() as $old => $new) {
                if ($model->isDirty($new)) {
                    $model->setAttribute($old, $model->getAttribute($new));
                } elseif ($model->isDirty($old)) {
                    $model->setAttribute($new, $model->getAttribute($old));
                }
            }
        });
    }

    /**
     * Old column => new column, for every pair kept in sync.
     *
     * @return array<string, string>
     */
    abstract public function renamedColumns(): array;
}
