<?php

namespace App\Models;

use App\Traits\HasTranslation;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Employee extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia , HasTranslation;

    protected $guarded = ['id'];

    protected $casts = [
        'dob' => 'datetime',
        'actual_dob' => 'datetime',
        'board_member_started_at' => 'datetime',
        'board_member_end_at' => 'datetime',
        'is_active' => 'boolean',
        'is_board_member' => 'boolean',
        'children' => 'integer',
    ];
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->getTranslatableAttribute('name'),
            set: fn($value) => $this->setTranslatableAttribute('name', $value)
        );
    }
    protected function father_name(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->getTranslatableAttribute('father_name'),
            set: fn($value) => $this->setTranslatableAttribute('father_name', $value)
        );
    }
    protected function mother_name(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->getTranslatableAttribute('mother_name'),
            set: fn($value) => $this->setTranslatableAttribute('mother_name', $value)
        );
    }

    protected function address(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->getTranslatableAttribute('address'),
            set: fn($value) => $this->setTranslatableAttribute('address', $value)
        );
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
    public function contracts()
    {
        return $this->hasMany(Contract::class, 'employee_id')->where('is_active', true);
    }

    public function latestActiveContract(): Contract
    {
        return $this->contracts()->with('position')->latest()->first();
    }

    public function defaultDeliverItems(): Employee|\Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(DefaultDeliverable::class,'employee_id');
    }

    public function positions(): BelongsToMany
    {
        return $this->belongsToMany(Position::class, 'contracts', 'employee_id', 'position_id')->withPivot(['id', 'contract_type_id', 'department_id', 'job_level_id', 'is_active', 'start_date', 'end_date', 'terminated_at', 'salary', 'currency', 'weekly_hours', 'yearly_leave_days', 'work_days', 'work_hours']);
    }

    public function scopeInvolvedInProgram($query, $programId)
    {
        return $query->whereHas('contracts.position.center', function ($q) use ($programId) {
            $q->where('program_id', $programId);
        });
    }

    public function educations()
    {
        return $this->hasMany(Education::class);
    }

    public function courses()
    {
        return $this->hasMany(Course::class);
    }

    public function experiences()
    {
        return $this->hasMany(Experience::class);
    }

    public function badges()
    {
        return $this->hasMany(Badge::class);
    }

    public function evaluations()
    {
        return $this->hasMany(Evaluation::class);
    }

    public function relatives()
    {
        return $this->hasMany(Relative::class);
    }

    public function checklists()
    {
        return $this->hasMany(Checklist::class);
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class);
    }

    public function techManager()
    {
        return $this->belongsTo(Employee::class, 'tech_manager_id');
    }

    public function directManager()
    {
        return $this->belongsTo(Employee::class, 'direct_manager_id');
    }

    public function items()
    {
        return $this->hasMany(EmployeeItem::class);
    }

    public function jobLevels(): BelongsToMany
    {
        return $this->belongsToMany(JobLevel::class, 'contracts', 'employee_id', 'job_level_id')->withPivot(['id', 'contract_type_id', 'department_id', 'job_level_id', 'is_active', 'start_date', 'end_date', 'terminated_at', 'salary', 'currency', 'weekly_hours', 'yearly_leave_days', 'work_days', 'work_hours']);
    }

    public function contractTypes(): BelongsToMany
    {
        return $this->belongsToMany(ContractType::class, 'contracts', 'employee_id', 'contract_type_id')->withPivot(['id', 'contract_type_id', 'department_id', 'job_level_id', 'is_active', 'start_date', 'end_date', 'terminated_at', 'salary', 'currency', 'weekly_hours', 'yearly_leave_days', 'work_days', 'work_hours']);
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'contracts', 'employee_id', 'department_id')->withPivot(['id', 'contract_type_id', 'department_id', 'job_level_id', 'is_active', 'start_date', 'end_date', 'terminated_at', 'salary', 'currency', 'weekly_hours', 'yearly_leave_days', 'work_days', 'work_hours']);
    }

    public function account()
    {
        return $this->hasOne(User::class, 'employee_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->useFallbackUrl('https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&color=be1f24&background=FFF')
            ->singleFile();
    }
}
