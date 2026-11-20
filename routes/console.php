<?php

/*
| No job is scheduled here. Each module, and the platform itself, lists its
| background jobs in a ScheduleSource registered with ModuleRegistry from its
| own ServiceProvider (App\Console\PlatformSchedule, Modules\Hr\Console\
| HrSchedule, Modules\Tracking\Console\TrackingSchedule), and
| AppServiceProvider applies them all. tests/Feature/ScheduleTest.php pins
| the full list.
*/
