<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class News extends Model
{
    protected $table = 'hchr_news';

    protected $fillable = [
        'title',
        'description',
        'photo',
        'display_until',
        'published_at',
        'newsable_id',
        'newsable_type',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'display_until' => 'datetime',
    ];

    public function newsable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->display_until && $this->display_until->isPast();
    }

    public function getNewsableNameAttribute(): string
    {
        if (!$this->newsable) {
            return __('general.all_org');
        }

        $relation = $this->newsable;
        $type = class_basename($this->newsable_type);

        return match ($type) {
            'City' => __('general.city') . ': ' . $relation->name,
            'Center' => __('general.center') . ': ' . $relation->name,
            'Program' => __('general.program') . ': ' . $relation->name,
            default => $relation->name ?? __('general.n_a'),
        };
    }
}
