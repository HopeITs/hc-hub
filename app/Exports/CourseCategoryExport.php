<?php

namespace App\Exports;

use App\Models\CourseCategory;
use Maatwebsite\Excel\Concerns\FromCollection;

class CourseCategoryExport implements FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return CourseCategory::all();
    }
}
