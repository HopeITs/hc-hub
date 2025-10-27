<?php

namespace App\Notifications;

use App\Models\Folder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FolderShared extends Notification
{
    use Queueable;

    public function __construct(public Folder $folder, public string $creatorName) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => "New Folder Shared: {$this->folder->name}",
            'body' => "{$this->creatorName} shared the folder '{$this->folder->name}' with you. You can now access it in Shared Folders.",
            'url' => route('shared-folders.index', ['folderId' => $this->folder->id]),
        ];
    }
}
