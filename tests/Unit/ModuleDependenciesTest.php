<?php

/*
 * D10 (docs/hub-unification/02-target-design.md §1): Core depends on no
 * module, and HR does not depend on Tracking. Tracking's relations on HR and
 * Core models are registered from TrackingServiceProvider instead.
 */

arch('hr does not depend on tracking')
    ->expect('Modules\Hr')
    ->not->toUse('Modules\Tracking');
