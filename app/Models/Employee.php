<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Employee extends Model
{
    use HasFactory, HasTranslations;

    protected $guarded = ['id'];

    public array $translatable = ['name', 'father_name', 'mother_name', 'address'];

    protected $casts = [
        'dob'                     => 'datetime',
        'board_member_started_at' => 'datetime',
        'board_member_end_at'     => 'datetime',
        'is_active'               => 'boolean',
        'is_board_member'         => 'boolean',
        'children'                => 'integer',
    ];

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    public function positions(): BelongsToMany
    {
        return $this->belongsToMany(Position::class);
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

    public function jobLevel()
    {
        return $this->belongsTo(JobLevel::class);
    }

    public function contractType()
    {
        return $this->belongsTo(ContractType::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function account()
    {
        return $this->hasOne(User::class, 'employee_id');
    }
}
