<?php

use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;
use Modules\Core\CoreServiceProvider;
use Modules\Hr\HrServiceProvider;
use Modules\Tracking\TrackingServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    CoreServiceProvider::class,
    TrackingServiceProvider::class,
    HrServiceProvider::class,
];
