<?php

namespace App\Livewire\ActivityLog;

use Spatie\Activitylog\Models\Activity as ActivityLog;
use App\Models\User;
use RamonRietdijk\LivewireTables\Columns\Column;
use RamonRietdijk\LivewireTables\Filters\SelectFilter;
use RamonRietdijk\LivewireTables\Livewire\LivewireTable;

class ActivityLogTable extends LivewireTable
{
    protected string $model = ActivityLog::class;

    protected function query(): \Illuminate\Database\Eloquent\Builder
    {
        return ActivityLog::query()
            ->with(['causer.profile'])
            ->latest();
    }

    protected function columns(): array
    {
        return [

            Column::make(__('User'))
                ->sortable()
                ->searchable()
                ->displayUsing(function ($value, $log) {
                    $profile = $log->causer?->profile;

                    if (! $profile) {
                        return '-';
                    }

                    return app()->getLocale() === 'ar'
                        ? $profile->name_ar
                        : $profile->name_en;
                }),

            Column::make(__('Action'), 'description')
                ->sortable()
                ->searchable(),

            Column::make(__('Model'), 'subject_type')
                ->sortable()
                ->searchable()
                ->displayUsing(fn($value) => class_basename($value)),

            Column::make(__('Event'), 'event')
                ->sortable()
                ->searchable(),

            Column::make(__('Record ID'), 'subject_id')
                ->sortable(),

            Column::make(__('Changes'), 'properties')
                ->displayUsing(function ($value) {
                    if (!$value) return '-';

                    $json = json_decode($value, true);

                    $before = $json['old'] ?? [];
                    $after = $json['attributes'] ?? [];

                    return 'Old: ' . json_encode($before) . ' | New: ' . json_encode($after);
                })->asHtml(),

            Column::make(__('Date'), 'created_at')
                ->sortable()
                ->displayUsing(fn($value) => $value->format('Y-m-d H:i')),
        ];
    }

    protected function filters(): array
    {
        return [

            SelectFilter::make(__('User'), 'causer_id')
                ->options(
                    User::query()
                        ->with('profile')
                        ->get()
                        ->mapWithKeys(function ($user) {
                            $profile = $user->profile;

                            $name = $profile
                                ? (app()->getLocale() === 'ar'
                                    ? $profile->name_ar
                                    : $profile->name_en)
                                : 'Unknown';

                            return [$user->id => $name];
                        })
                        ->toArray()
                ),

            SelectFilter::make(__('Model'), 'subject_type')
                ->options(
                    ActivityLog::query()
                        ->select('subject_type')
                        ->distinct()
                        ->pluck('subject_type')
                        ->mapWithKeys(fn($type) => [$type => class_basename($type)])
                        ->toArray()
                ),

            SelectFilter::make(__('Event'), 'event')
                ->options(
                    ActivityLog::query()
                        ->select('event')
                        ->distinct()
                        ->pluck('event', 'event')
                        ->toArray()
                ),
        ];
    }

    protected function actions(): array
    {
        return [];
    }
}
