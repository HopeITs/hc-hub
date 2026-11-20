<?php

use Illuminate\Support\Facades\Route;

/*
| The organisation's mailing lists. Every member of staff reads them and
| emails one; editing members (mail-lists.manage) and confirming pending
| additions (mail-lists.confirm) are checked inside the page. Loaded by
| HrServiceProvider inside the auth + 2FA group.
*/
Route::livewire('hr/mail-lists', 'hr::mail-lists.index')
    ->name('hr.mail-lists.index');

// What each list should hold by HR data, and who is missing or has left: HR only.
Route::livewire('hr/mail-lists/check', 'hr::mail-lists.check')
    ->can('mail-lists.manage')
    ->name('hr.mail-lists.check');
