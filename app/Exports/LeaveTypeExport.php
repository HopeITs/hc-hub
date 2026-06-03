<?php

namespace App\Exports;

use App\Models\LeaveType;
use Maatwebsite\Excel\Concerns\FromCollection;

class LeaveTypeExport implements FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return LeaveType::all();
    }
}
