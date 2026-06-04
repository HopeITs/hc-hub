<?php

namespace App\Exports;

use App\Models\Center;
use Maatwebsite\Excel\Concerns\FromCollection;

class CenterExport implements FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Center::all();
    }
}
