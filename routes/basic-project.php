<?php


use App\Livewire\Basics\{ChecklistItemModule,
    CityModule,
    ContractTypeModule,
    DepartmentModule,
    JobLevelModule,
    LeaveTypeModule,
    AnnualLeaveRuleModule
};
use App\Livewire\Position\OrgChart;
use Illuminate\Support\Facades\Route;



Route::get('cities', CityModule::class)->name('cities.index');
Route::get('contract-types', ContractTypeModule::class)->name('contract-types.index')->middleware('role:HR|super-admin');
Route::get('checklist-items', ChecklistItemModule::class)->name('basic-checklist-items.index');
Route::get('leave-types', LeaveTypeModule::class)->name('leave-types.index');
Route::get('job-levels', JobLevelModule::class)->name('job-levels.index')->middleware('role:HR|super-admin');
Route::get('departments', DepartmentModule::class)->name('departments.index');
Route::get('annual-leave-rules', AnnualLeaveRuleModule::class)->name('annual-leave-rules.index')->middleware('role:HR|super-admin');;


Route::get('/org-chart', OrgChart::class)->name('org.chart');








