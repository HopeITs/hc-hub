<?php

namespace App\Traits;

use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;

trait WithAlerts
{
    public function successToast($message = 'general.success-message', $position = 'top-start')
    {

        LivewireAlert::title(__($message))
            ->success()
            ->toast()
            ->position($position)
            ->show();

    }
}
