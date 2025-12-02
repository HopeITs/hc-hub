<?php

namespace App\Livewire\Forms\Employee;

use App\Models\Course;
use App\Models\Employee;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Form;

class CourseForm extends Form
{
    public ?Employee $employee = null;
    public ?Course $course = null;

    /** @var array<int, array{title:string|null, issuing_organization:string|null, date:string|null, is_approved:int|bool|null}> */
    public array $courses = [];

    public function setEmployee(Employee $employee): void
    {
        $this->employee = $employee->load('courses');

        // Seed existing rows
        $this->courses = $employee
            ->courses()
            ->select('id', 'title', 'issuing_organization', 'issue_date', 'is_approved')
            ->get()
            ->map(
                fn($c) => [
                    'id' => $c->id,
                    'title' => $c->title,
                    'issuing_organization' => $c->issuing_organization,
                    'issue_date' => optional($c->issue_date)->toDateString(), // Y-m-d for <input type="date">
                    'is_approved' => (int) $c->is_approved,
                    'course_attachment' => $c->getFirstMediaUrl('file'),
                    'file_name' => $c->getFirstMedia('file')->file_name ?? null,
                ],
            )
            ->toArray();
    }

    public function rules(): array
    {
        return [
            'courses' => ['array'],
            'courses.*.id' => ['nullable', 'exists:courses,id'],
            'courses.*.title' => ['required', 'string', 'max:255'],
            'courses.*.issuing_organization' => ['required', 'string', 'max:255'],
            'courses.*.issue_date' => ['nullable', 'date'],
            'courses.*.is_approved' => ['nullable', 'boolean'],
            'courses.*.course_attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ];
    }

    public function addCourse(): void
    {
        $this->courses[] = [
            'id' => null,
            'title' => '',
            'issuing_organization' => '',
            'issue_date' => null,
            'is_approved' => 0,
            'course_attachment' => null,
            'file_name' => null,
        ];
    }

    public function remove(int $index, $id = null): void
    {
        if (!array_key_exists($index, $this->courses)) {
            return;
        }

        unset($this->courses[$index]);

        if (isset($id)) {
            Course::find($id)->delete();
        }
        // Reindex so Blade indexes and array keys stay aligned
        $this->courses = array_values($this->courses);
    }

    public function save(): void
    {
        $this->validate();

        foreach ($this->courses as $index => $course) {
            if (array_key_exists('id', $course) && isset($course['id'])) {
                $this->course = Course::find($course['id']);
                $this->course->update($course);
            } else {
                $course['employee_id'] = $this->employee->id;
                $this->course = Course::create($course);
            }

            if ($course['course_attachment'] instanceof TemporaryUploadedFile) {
                $this->course
                    ->addMedia($course['course_attachment']->getRealPath())
                    ->usingFileName('course-file-' . $index . '.' . $course['course_attachment']->getClientOriginalExtension())
                    ->toMediaCollection('file');
            }
        }
    }
}
