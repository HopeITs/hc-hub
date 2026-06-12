<?php

namespace App\Models;

use App\Traits\HasTranslation;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Position extends Model
{
    use HasFactory, HasTranslation, LogsActivity;

    protected $guarded = ['id'];

    protected $casts = [
        'max_emp'    => 'integer',
        'exist_emp'  => 'integer',
        'is_active'  => 'boolean',
    ];

    // --------------------------------------------
    // Accessors & Mutators
    // --------------------------------------------

    protected function title(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->getTranslatableAttribute('title'),
            set: fn($value) => $this->setTranslatableAttribute('title', $value)
        );
    }

    // --------------------------------------------
    // Relationships
    // --------------------------------------------

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'position_id');
    }

    public function activeContracts(): HasMany
    {
        return $this->contracts()
            ->where(function ($q) {
                $q->where('is_active', true)
                    ->orWhere(function ($q2) {
                        $q2->whereNull('terminated_at')
                            ->whereDate('start_date', '<=', now())
                            ->whereDate('end_date', '>=', now());
                    });
            })
            ->with('employee');
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class, 'center_id');
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class, 'program_id');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function jobLevel(): BelongsTo
    {
        return $this->belongsTo(JobLevel::class, 'job_level_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function directManager(): BelongsTo
    {
        return $this->belongsTo(self::class, 'direct_manager_id');
    }

    public function techManager(): BelongsTo
    {
        return $this->belongsTo(self::class, 'tech_manager_id');
    }

    public function directTeam(): HasMany
    {
        return $this->hasMany(self::class, 'direct_manager_id')->orderBy('job_level_id');
    }

    public function techTeam(): HasMany
    {
        return $this->hasMany(self::class, 'tech_manager_id')->orderBy('job_level_id');
    }

    // --------------------------------------------
    // Query scopes
    // --------------------------------------------

    /**
     * Positions reachable from the given city: either assigned directly to the
     * city, or assigned to a center that belongs to it. Single source of truth
     * for city scoping; keep contract create/edit filters aligned with it.
     */
    public function scopeInCity($query, int $cityId)
    {
        return $query->where(function ($q) use ($cityId) {
            $q->where('city_id', $cityId)
              ->orWhereHas('center', fn ($c) => $c->where('city_id', $cityId));
        });
    }

    // --------------------------------------------
    // Helpers
    // --------------------------------------------

    public function employee(): ?Employee
    {
        return $this->activeContracts()->first()?->employee;
    }

    public function canAssign(): bool
    {
        return $this->exist_emp < $this->max_emp;
    }

    public function inc(): void
    {
        $this->increment('exist_emp');
    }

    public function dec(): void
    {
        if ($this->exist_emp > 0) {
            $this->decrement('exist_emp');
        }
    }

    // --------------------------------------------
    // Activity Logging
    // --------------------------------------------

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('position')
            ->logOnly([
                'title',              // translatable
                'center_id',
                'program_id',
                'city_id',
                'job_level_id',
                'department_id',
                'direct_manager_id',
                'tech_manager_id',
                'max_emp',
                'exist_emp',
                'is_active',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
