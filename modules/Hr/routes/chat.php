<?php

use Illuminate\Support\Facades\Route;
use Modules\Hr\Http\Controllers\ChatFileController;
use Modules\Hr\Livewire\Chat\Page;

/*
| The chat. No permission of its own: every signed-in employee may chat, and
| ChatConversationPolicy (membership only, no role exception) guards each
| conversation. Everything 404s while CHAT_ENABLED is off. Loaded by
| HrServiceProvider inside the auth + 2FA group.
|
| The full page is opened only from a chat tab's maximise button. A message
| with files (encrypted on arrival) is posted here, and a file comes back out
| decrypted for members of its chat only.
*/
Route::livewire('hr/chat', Page::class)->name('hr.chat');
Route::post('hr/chat/{conversation}/messages', [ChatFileController::class, 'store'])
    ->whereNumber('conversation')->name('hr.chat.messages.store');
Route::get('hr/chat/files/{attachment}', [ChatFileController::class, 'show'])
    ->whereNumber('attachment')->name('hr.chat.files.show');
