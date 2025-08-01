<?php

use App\Livewire\Basics\ChecklistItemModule;
use App\Livewire\Basics\CityModule ;
use App\Livewire\Basics\ContractTypeModule ;
use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
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
});

require __DIR__.'/auth.php';
