<?php

declare(strict_types=1);

use Modules\Hr\Support\Recruitment\RecruitmentBootstrap;

/*
| Recruitment: requests and approvals, campaigns and publishing, applicants,
| interviews, offers and the indicator rules. Each part keeps its routes in
| routes/recruitment/<part>.php; all of them are loaded here, inside the
| auth + two-factor group of the HR module.
*/

RecruitmentBootstrap::boot();

foreach (glob(__DIR__.'/recruitment/*.php') ?: [] as $file) {
    require $file;
}
