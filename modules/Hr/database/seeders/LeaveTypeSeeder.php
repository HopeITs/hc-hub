<?php

namespace Modules\Hr\Database\Seeders;

use Modules\Hr\Models\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        $leaveTypes = config('samples.sampleData.leaveTypes');

        foreach ($leaveTypes as $leaveType) {
            LeaveType::create($leaveType);
        }
    }
}
