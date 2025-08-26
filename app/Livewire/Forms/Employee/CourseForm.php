<?php

namespace App\Livewire\Forms\Employee;

use App\Models\Course;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Form;
use Livewire\WithFileUploads;

class CourseForm extends Form
{
    use WithFileUploads;

    public ?Course $course = null ;

   public  $title;

   public $employee_id ;
   public $issuing_organization;
   public $date;
   public $is_approved;


    public function addCourse(): void
    {
        $this->courses[] = ['title' => '', 'issuing_organization' => '', 'date' => '', 'is_approved' => 0];
    }

    public function removeCourse(int $index): void
    {
        unset($this->courses[$index]);
        $this->courses = array_values($this->courses);
    }



    public function saveCourses(): void
    {
        $this->validate([
            'courses' => 'array',
            'courses.*.title' => 'required_with:courses.*.issuing_organization|string|max:255|nullable',
        ]);

        $this->employee->courses()->delete();
        $this->employee->courses()->createMany($this->courses);
    }


}
