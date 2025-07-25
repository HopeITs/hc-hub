<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = ['id', 'full_name', 'years_of_service', 'direct_manager_id', 'tech_manager_id', 'national_number', 'dob', 'actual_job', 'civil_registry_office', 'civil_registry_number', 'place_of_registration', 'gender', 'father_name', 'mother_name', 'nationality', 'material_status', 'children', 'work_phone', 'personal_phone', 'personal_email', 'work_email', 'address', 'skills', 'interests', 'is_active'];

    public function contracts()
    {
        return $this->hasMany(Contract::class);
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

    public function checklist()
    {
        return $this->hasOne(Checklist::class);
    }
}
