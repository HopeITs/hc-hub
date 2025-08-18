<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Translatable\HasTranslations;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Employee extends Model
{
    use HasFactory, HasTranslations;

    protected $guarded = ['id'];

    public array $translatable = ['name', 'father_name', 'mother_name', 'address'];

    public function contracts()
    {
        return $this->hasMany(Contract::class , 'employee_id');
    }

    public function positions(): BelongsToMany
    {
        return $this->belongsToMany(Position::class, 'contracts', 'employee_id', 'position_id')
            ->withPivot([
                'id','contract_type_id','department_id','job_level_id',
                'is_active','start_date','end_date','terminated_at',
                'salary','currency','weekly_hours','yearly_leave_days','work_days','work_hours',
            ]);
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

    public function techManager()
    {
        return $this->belongsTo(Employee::class, 'tech_manager_id');
    }

    public function directManager()
    {
        return $this->belongsTo(Employee::class, 'direct_manager_id');
    }

    public function jobLevels(): BelongsToMany
    {
        return $this->belongsToMany(JobLevel::class, 'contracts', 'employee_id', 'job_level_id')
            ->withPivot([
                'id','contract_type_id','department_id','job_level_id',
                'is_active','start_date','end_date','terminated_at',
                'salary','currency','weekly_hours','yearly_leave_days','work_days','work_hours',
            ]);
    }

    public function contractTypes(): BelongsToMany
    {
        return $this->belongsToMany(ContractType::class, 'contracts', 'employee_id', 'contract_type_id')
            ->withPivot([
                'id','contract_type_id','department_id','job_level_id',
                'is_active','start_date','end_date','terminated_at',
                'salary','currency','weekly_hours','yearly_leave_days','work_days','work_hours',
            ]);
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'contracts', 'employee_id', 'department_id')
            ->withPivot([
                'id','contract_type_id','department_id','job_level_id',
                'is_active','start_date','end_date','terminated_at',
                'salary','currency','weekly_hours','yearly_leave_days','work_days','work_hours',
            ]);
    }

    public function account(){
        return $this->hasOne(User::class, 'employee_id');
    }
}
