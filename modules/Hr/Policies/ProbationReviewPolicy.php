<?php

declare(strict_types=1);

namespace Modules\Hr\Policies;

use Modules\Core\Models\User;
use Modules\Hr\Models\ProbationReview;
use Modules\Hr\Support\Contracts\ProbationReviews;

/**
 * Who sees and answers a trial's last-third review. The scope is the
 * service's: the direct manager of the trial's position, and HR of the
 * employee's city (EmployeePolicy::update); HR may answer only when the
 * position has no manager running it. There is no permission to name.
 */
class ProbationReviewPolicy
{
    public function view(User $user, ProbationReview $review): bool
    {
        return app(ProbationReviews::class)->canView($user, $review);
    }

    public function decide(User $user, ProbationReview $review): bool
    {
        return app(ProbationReviews::class)->canDecide($user, $review);
    }
}
