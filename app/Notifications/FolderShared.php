<?php

namespace App\Notifications;

use App\Models\Folder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FolderShared extends Notification
{
    use Queueable;

    protected Folder $folder;
    protected string $creatorName;

    public function __construct(Folder $folder, string $creatorName)
    {
        $this->folder = $folder;
        $this->creatorName = $creatorName;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'message' => "{$this->creatorName} created folder '{$this->folder->name}' and shared it with you.",
            'url' => route('shared-folders.index', ['folderId' => $this->folder->id]),
        ];
    }
}
