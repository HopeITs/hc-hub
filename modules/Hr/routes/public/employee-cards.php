<?php

use Illuminate\Support\Facades\Route;
use Modules\Hr\Http\Controllers\Employees\EmployeeCardController;

/*
| The public digital ID card: /ID/[city 3 digits][program 3 digits][employee id]-[index].
| The constraint only bounds the segment; DigitalId::resolve() parses strictly
| and every invalid card gets the same page and 404. Loaded inside 'web' only.
*/
Route::get('/ID/{card}', [EmployeeCardController::class, 'show'])
    ->where('card', '[^/]{1,64}')
    ->middleware('throttle:30,1')
    ->name('hr.employees.card.show');

// The card's photo: a signed link minted by the card page, valid for minutes.
Route::get('/ID/{employee}/photo', [EmployeeCardController::class, 'photo'])
    ->whereNumber('employee')
    ->middleware(['signed', 'throttle:60,1'])
    ->name('hr.employees.card.photo');
