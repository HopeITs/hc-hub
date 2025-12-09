<?php

use App\Http\Controllers\ContractExportController;
use App\Livewire\Archives\ArchivedFilesModule;
use App\Livewire\Basics\{ChecklistItemModule,
    CityModule,
    ContractTypeModule,
    DepartmentModule,
    JobLevelModule,
    LeaveTypeModule
};
use App\Livewire\ChecklistItems\{AllItems, DoneRequests, PendingItems, PendingReturns};
use App\Livewire\Contract\{ContractCreate, ContractDetails, ContractEdit, ContractsModule};
use App\Livewire\Statistics\ContractStatistics;
use App\Livewire\Statistics\GeneralStatistics;
use App\Livewire\Employee\{EmployeeCreate,
    EmployeeEdit,
    EmployeeModule,
    EmployeeProfile,
    Leave\AllLeaveRequestModule,
    Leave\CreateLeaveRequest,
    Leave\EmpLeaveBalanceModule,
    Leave\MyLeaveRequestModule,
    Leave\MyLeaveRequestTable
};
use App\Livewire\Employee\EmployeeStatistics;
use App\Livewire\Position\{OrgChart, PositionCreate, PositionEdit, PositionsModule};
use App\Livewire\ProgramManagement\{Center\CenterDetails,
    Center\CentersModule,
    DefaultDeliverable\DefaultDeliverablesModule,
    Program\Create as ProgramCreate,
    Program\Detail as ProgramDetail,
    Program\Index as ProgramIndex,
    Program\Update as ProgramUpdate
};
use App\Livewire\Settings\{Appearance, Password, Profile};
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;


Route::get('cities', CityModule::class)->name('cities.index');
Route::get('contract-types', ContractTypeModule::class)->name('contract-types.index')->middleware('role:HR|SUPER ADMIN');
Route::get('checklist-items-basics', ChecklistItemModule::class)->name('basic-checklist-items.index');
Route::get('leave-types', LeaveTypeModule::class)->name('leave-types.index');
Route::get('job-levels', JobLevelModule::class)->name('job-levels.index');
Route::get('departments', DepartmentModule::class)->name('departments.index');
Route::prefix('centers')
    ->name('centers.')
    ->group(function () {
        Route::get('/', CentersModule::class)->name('index');
        Route::get('/create', \App\Livewire\ProgramManagement\Center\CenterCreate::class)->name('create'); // ✅ Add this
        Route::get('/{center}', CenterDetails::class)->name('show');
    });


Route::get('/org-chart', OrgChart::class)->name('org.chart');








