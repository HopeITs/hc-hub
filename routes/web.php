<?php

use App\Livewire\Basics\ChecklistItemModule;
use App\Livewire\Basics\CityModule;
use App\Livewire\Basics\ContractTypeModule;
use App\Livewire\Basics\DepartmentModule;
use App\Livewire\ProjectManagement\CentersModule;
use App\Livewire\Basics\JobLevelModule;
use App\Livewire\Basics\LeaveTypeModule;
use App\Livewire\ProjectManagement\PositionsModule;
use App\Livewire\ProjectManagement\ProjectDetail;
use App\Livewire\ProjectManagement\ProjectModule;
use App\Livewire\ProjectManagement\RequiredPositionModule;
use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Livewire\Employee\EmployeeModule;
use App\Livewire\Employee\EmployeeCreate;
use App\Livewire\Employee\EmployeeEdit;
use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', Profile::class)->name('settings.profile');
    Route::get('settings/password', Password::class)->name('settings.password');
    Route::get('settings/appearance', Appearance::class)->name('settings.appearance');

    Route::get('cities', CityModule::class)->name('cities.index');
    Route::get('contract-types', ContractTypeModule::class)->name('contract-types.index');
    Route::get('checklist-items', ChecklistItemModule::class)->name('checklist-items.index');
    Route::get('leave-types', LeaveTypeModule::class)->name('leave-types.index');
    Route::get('job-levels', JobLevelModule::class)->name('job-levels.index');
    Route::get('departments', DepartmentModule::class)->name('departments.index');
    Route::get('projects', ProjectModule::class)->name('projects.index');
    Route::get('projects/{slug}', ProjectDetail::class)->name('projects.show');
    Route::get('required-positions', RequiredPositionModule::class)->name('required-positions.index');
    Route::get('/centers', CentersModule::class)->name('centers.index');
    Route::get('/project-management/centers/{center}/positions', PositionsModule::class)->name('centers.positions');
});
Route::prefix('employees')->name('employees.')->group(function () {
    Route::get('/', EmployeeModule::class)->name('index');
    Route::get('/create', EmployeeCreate::class)->name('create');
    Route::get('/{employee}/edit', EmployeeEdit::class)->name('edit');
});

require __DIR__.'/auth.php';
