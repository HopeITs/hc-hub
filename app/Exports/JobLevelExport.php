<?php

namespace App\Exports;

use App\Models\JobLevel;
use Maatwebsite\Excel\Concerns\FromCollection;

class JobLevelExport implements FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return JobLevel::all();
    }
}
