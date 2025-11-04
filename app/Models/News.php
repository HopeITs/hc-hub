<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class News extends Model
{
    protected $table = 'hchr_news';

    protected $fillable = ['title', 'description', 'photo', 'display_duration', 'published_at', 'newsable_id', 'newsable_type'];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function newsable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getDurationInHoursAttribute(): int
    {
        return match ($this->display_duration) {
            '1_hour' => 1,
            '2_hours' => 2,
            '3_hours' => 3,
            '4_hours' => 4,
            '5_hours' => 5,
            '6_hours' => 6,
            '7_hours' => 7,
            '8_hours' => 8,
            '1_day' => 24,
            '2_days' => 48,
            '3_days' => 72,
            '4_days' => 96,
            '5_days' => 120,
            '6_days' => 144,
            '1_week' => 168,
        };
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->published_at ? $this->published_at->diffInHours(Carbon::now()) >= $this->duration_in_hours : false;
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
