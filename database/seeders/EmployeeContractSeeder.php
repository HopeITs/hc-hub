<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Contract;
use Carbon\Carbon;

class EmployeeContractSeeder extends Seeder
{
    public function run(): void
    {
        $contracts = [
            ['employee' => 'fredy.youssef', 'position' => 'Chief Executive Officer', 'start_date' => '2018-01-01'],
            ['employee' => 'safir.salim', 'position' => 'Deputy Chief Executive Officer', 'start_date' => '2018-01-01'],
            ['employee' => 'judy.dadaoghlian', 'position' => 'IT Manager', 'start_date' => '2018-01-01'],
            ['employee' => 'george.abdelnour', 'position' => 'Communication Manager / Syria', 'start_date' => '2018-02-12'],
            ['employee' => 'lana.abdo', 'position' => 'HR Generalist', 'start_date' => '2018-02-01'],
            ['employee' => 'majd.zrek', 'position' => 'Legal Affairs Consultant', 'start_date' => '2018-03-01'],
            ['employee' => 'rama.daaboul', 'position' => 'Second Deputy Chief Executive Officer', 'start_date' => '2018-09-01'],
            ['employee' => 'magie.najjar', 'position' => 'Center Keeper', 'start_date' => '2018-11-16'],
            ['employee' => 'shadi.makdessi', 'position' => 'Senior Financial Manager', 'start_date' => '2019-02-01'],
            ['employee' => 'antoine.arbaji', 'position' => 'Program Manager', 'start_date' => '2019-10-01'],
        ];

        foreach ($contracts as $data) {
            $employee = Employee::where('username', $data['employee'])->first();
            $position = Position::where('title_en', $data['position'])->first();

            if (!$employee || !$position) {
                continue;
            }

            Contract::create([
                'employee_id' => $employee->id,
                'position_id' => $position->id,
                'contract_type_id' => 1, // تأكد من وجود نوع عقد بالمعرف 1
                'start_date' => Carbon::parse($data['start_date']),
                'end_date' => Carbon::parse($data['start_date'])->addYears(1),
                'terminated_at' => null,
                'reason_of_termination' => null,
                'salary' => 1000000.00, // مثال، يمكن تعديله حسب الحاجة
                'currency' => 1, // 1 = SYP
                'salary_per' => 1, // 1 = شهري
                'work_days' => json_encode(['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday']),
                'work_hours' => json_encode(['start' => '09:00', 'end' => '17:00']),
                'weekly_hours' => 40,
                'is_active' => true,
            ]);
        }
    }
}
