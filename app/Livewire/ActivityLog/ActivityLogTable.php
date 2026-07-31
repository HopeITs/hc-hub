<?php

namespace App\Livewire\ActivityLog;

use App\Models\User;
use Illuminate\Support\Str;
use RamonRietdijk\LivewireTables\Columns\Column;
use RamonRietdijk\LivewireTables\Filters\SelectFilter;
use RamonRietdijk\LivewireTables\Livewire\LivewireTable;
use Spatie\Activitylog\Models\Activity as ActivityLog;

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

                    return $profile->name;
                }),

            Column::make(__('Action'), 'description')
                ->sortable()
                ->searchable(),

            Column::make(__('Model'), 'subject_type')
                ->sortable()
                ->searchable()
                ->displayUsing(fn ($value) => class_basename($value)),

            Column::make(__('Event'), 'event')
                ->sortable()
                ->searchable(),

            Column::make(__('Record ID'), 'subject_id')
                ->sortable(),

            // Stored XSS sink: this column rendered the logged old/new values
            // as raw HTML. Those values come from user-editable fields (an
            // employee name, a note, an address), so a payload saved into any
            // audited record would execute for every administrator who later
            // opened the activity log — a privileged audience, which is what
            // makes it worth more than a self-XSS.
            //
            // asHtml() is dropped: the changes are plain text and the table
            // library escapes non-HTML columns. Every value is additionally
            // run through e() so that reinstating asHtml() later cannot
            // silently reopen the hole.
            Column::make(__('Changes'), 'properties')
                ->displayUsing(function ($value): string {
                    if (! $value) {
                        return '-';
                    }

                    $json = json_decode($value, true) ?: [];

                    return 'Old: '.$this->summarise($json['old'] ?? [])
                        .' | New: '.$this->summarise($json['attributes'] ?? []);
                }),

            Column::make(__('Date'), 'created_at')
                ->sortable()
                ->displayUsing(fn ($value) => $value->format('Y-m-d H:i')),
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
                                ? $profile->name
                                : 'Unknown';

                            return [$user->id => $name];
                        })
                        ->toArray(),
                ),

            SelectFilter::make(__('Model'), 'subject_type')
                ->options(
                    ActivityLog::query()
                        ->select('subject_type')
                        ->distinct()
                        ->pluck('subject_type')
                        ->mapWithKeys(fn ($type) => [$type => class_basename($type)])
                        ->toArray(),
                ),

            SelectFilter::make(__('Event'), 'event')
                ->options(
                    ActivityLog::query()
                        ->select('event')
                        ->distinct()
                        ->pluck('event', 'event')
                        ->toArray(),
                ),
        ];
    }

    protected function actions(): array
    {
        return [];
    }

    /**
     * Render a logged attribute set as escaped, readable text.
     *
     * Nested values are JSON-encoded rather than printed structurally, so a
     * crafted array cannot smuggle markup through a child node.
     */
    private function summarise(mixed $attributes): string
    {
        if (! is_array($attributes) || $attributes === []) {
            return '—';
        }

        return collect($attributes)
            ->map(function ($value, $key): string {
                $rendered = is_scalar($value) || $value === null
                    ? (string) $value
                    : json_encode($value);

                return e($key).': '.e(Str::limit((string) $rendered, 120));
            })
            ->implode(', ');
    }
}
