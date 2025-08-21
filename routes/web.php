<?php

use App\Livewire\Basics\ChecklistItemModule;
use App\Livewire\Basics\CityModule;
use App\Livewire\Basics\ContractTypeModule;
use App\Livewire\Basics\DepartmentModule;
use App\Livewire\Basics\JobLevelModule;
use App\Livewire\Basics\LeaveTypeModule;
use App\Livewire\Contract\ContractsModule;
use App\Livewire\Contract\ContractCreate;
use App\Livewire\Contract\ContractEdit;
use App\Livewire\Employee\EmployeeCreate;
use App\Livewire\Employee\EmployeeEdit;
use App\Livewire\Employee\EmployeeModule;
use App\Livewire\ProjectManagement\CentersModule;
use App\Livewire\ProjectManagement\DefaultDeliverablesModule;

use App\Livewire\Employee\Tabs\PersonalInfoTab;

use App\Livewire\ProjectManagement\PositionsModule;
use App\Livewire\ProjectManagement\Project\Detail as ProjectDetail;
use App\Livewire\ProjectManagement\Project\Create as ProjectCreate;
use App\Livewire\ProjectManagement\Project\Update as ProjectUpdate;
use App\Livewire\ProjectManagement\Project\Index as ProjectIndex;
use App\Livewire\ProjectManagement\RequiredPositionModule;
use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;

use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

Route::group(
    [
        'prefix' => LaravelLocalization::setLocale(),
//        'middleware' => ['localeSessionRedirect', 'localizationRedirect', 'localeViewPath']
    ],
    function () {

        Livewire::setUpdateRoute(function ($handle) {
            return Route::post('/livewire/update', $handle);
        });
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

            Route::get('required-positions', RequiredPositionModule::class)->name('required-positions.index');
            Route::get('/centers', CentersModule::class)->name('centers.index');
            Route::get('/project-management/centers/{center}/positions', PositionsModule::class)->name('centers.positions');
            Route::get('/default-deliverables', DefaultDeliverablesModule::class)->name('default-deliverables.index');

            Route::prefix('contracts')->name('contracts.')->group(function () {
                Route::get('/', ContractsModule::class)->name('index');
                Route::get('/create', ContractCreate::class)->name('create');
                Route::get('/{contract}/edit', ContractEdit::class)->name('edit');
            });


            Route::prefix('projects')->name('projects.')->group(function () {
                Route::get('/', ProjectIndex::class)->name('index');
                Route::get('/create', ProjectCreate::class)->name('create');
                Route::get('/edit/{project:slug}', ProjectUpdate::class)->name('edit');
                Route::get('/{slug}', ProjectDetail::class)->name('show');
            });
            Route::prefix('employees')->name('employees.')->group(function () {
                Route::get('/', EmployeeModule::class)->name('index');
                Route::get('/create', EmployeeCreate::class)->name('create');
                Route::get('/create/personal-info', PersonalInfoTab::class)->name('create.personal-info');
                Route::get('/{employee}/edit', EmployeeEdit::class)->name('edit');
            });
        });

        require __DIR__ . '/auth.php';
    }
);
