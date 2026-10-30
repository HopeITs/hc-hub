<?php

use Illuminate\Support\Facades\Route;
use Modules\Hr\Http\Controllers\EmployeeAvatarController;
use Modules\Hr\Livewire\EmployeeCards\ShowDigitalId;

// HR routes outside the locale group and outside 'auth'. Required from routes/web.php.

// Public ID card: /ID/[city 3 digits][program 3 digits][employee id]-[index]
// (access-policy.md §9 A2D-Q6). The constraint only bounds the segment; it is
// deliberately loose so malformed and old username-style links still reach
// ShowDigitalId, which parses strictly and answers every invalid card with
// the same "card is not valid" page and the same 404.
Route::get('/ID/{card}', ShowDigitalId::class)
    ->where('card', '[^/]{1,64}')
    ->middleware('throttle:30,1')
    ->name('digital-id.show');

// Photo for the public ID card. Outside the locale group and outside 'auth'
// because the card itself is unauthenticated; the signed URL is minted by
// ShowDigitalId only after the card link resolved to an active employee's
// current, enabled card, and it expires within minutes.
Route::get('/ID/{employee}/photo', [EmployeeAvatarController::class, 'digitalId'])
    ->middleware(['signed', 'throttle:60,1'])
    ->name('digital-id.avatar');
