<?php

declare(strict_types=1);

namespace Modules\Hr\Livewire\Concerns\Recruitment;

use Illuminate\Support\Facades\Gate;
use Modules\Hr\Models\RecruitmentApplication;
use Modules\Hr\Models\RecruitmentJobOffer;

/**
 * How the offer pages find their records: through the viewer's scope first
 * (RecruitmentJobOffer::visibleTo / RecruitmentApplication::visibleTo), then
 * the policy, so an id out of reach is a 404 and never a 403 that confirms it
 * exists. Needs AuthorizesRecruitment for viewer().
 */
trait ResolvesJobOffers
{
    protected function resolveOffer(int $offerId): RecruitmentJobOffer
    {
        $offer = RecruitmentJobOffer::query()->visibleTo($this->viewer())->findOrFail($offerId);
        Gate::authorize('view', $offer);

        return $offer;
    }

    protected function resolveApplication(int $applicationId): RecruitmentApplication
    {
        $application = RecruitmentApplication::query()->visibleTo($this->viewer())->findOrFail($applicationId);
        Gate::authorize('view', $application);

        return $application;
    }

    /** A person's name for the history lines; the hub's User::name falls back to the email. */
    protected static function personName(mixed $user): string
    {
        return $user?->name ?? '—';
    }
}
