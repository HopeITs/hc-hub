<?php

use Illuminate\Support\Facades\Route;
use Modules\Hr\Models\MessageTemplate;

/*
| HR's email and WhatsApp templates. The picker on a person's profile needs
| only message-templates.use and lives on that page. Loaded by
| HrServiceProvider inside the auth + 2FA group.
*/
Route::livewire('hr/message-templates', 'hr::message-templates.index')
    ->can('viewAny', MessageTemplate::class)
    ->name('hr.message-templates.index');
