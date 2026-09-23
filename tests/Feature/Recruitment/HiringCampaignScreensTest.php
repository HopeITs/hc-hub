<?php

namespace Tests\Feature\Recruitment;

use App\Enums\HiringCampaignStatus as Status;
use App\Enums\RecruitmentRequestStatus;
use App\Enums\RecruitmentRequestType;
use App\Livewire\Recruitment\Campaigns\Create;
use App\Livewire\Recruitment\Campaigns\Show;
use App\Models\HiringCampaign;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Vacancy;
use App\Services\HiringCampaignService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\Concerns\BuildsPublishedCampaignAssets;
use Tests\Concerns\BuildsWorkflowFixtures;
use Tests\Concerns\CreatesHrFixtures;
use Tests\TestCase;

/**
 * The screens that run a campaign's life.
 *
 * What they mostly have to get right is not offering a move the server would
 * refuse, and not hiding one it would allow — the buttons are asked of the
 * state machine rather than decided in the view, and these tests hold them to
 * that.
 */
class HiringCampaignScreensTest extends TestCase
{
    use BuildsPublishedCampaignAssets, BuildsWorkflowFixtures, CreatesHrFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('en');
    }

    public function test_hr_can_create_a_campaign_from_the_screen(): void
    {
        [$hr, $vacancy] = $this->seat();

        Livewire::actingAs($hr)
            ->test(Create::class)
            ->set('vacancyId', $vacancy->id)
            ->set('linkMode', HiringCampaign::INTERNAL)
            ->set('endsAt', now()->addMonth()->format('Y-m-d\TH:i'))
            ->call('save')
            ->assertHasNoErrors();

        $campaign = HiringCampaign::query()->sole();

        $this->assertSame(HiringCampaign::INTERNAL, $campaign->link_mode);
        $this->assertSame(Status::Draft, (int) $campaign->status);
    }

    /** The three modes run in sequence on one seat, and the screen offers all three. */
    public function test_the_create_screen_offers_every_link_mode(): void
    {
        [$hr] = $this->seat();

        Livewire::actingAs($hr)
            ->test(Create::class)
            ->assertSee(__('recruitment-campaign.modes.internal'))
            ->assertSee(__('recruitment-campaign.modes.public_open'))
            ->assertSee(__('recruitment-campaign.modes.invite_only'));
    }

    /** A window that makes no sense is refused on the way in, not stored. */
    public function test_the_create_screen_reports_a_window_that_ends_before_it_starts(): void
    {
        [$hr, $vacancy] = $this->seat();

        Livewire::actingAs($hr)
            ->test(Create::class)
            ->set('vacancyId', $vacancy->id)
            ->set('startsAt', now()->addDays(10)->format('Y-m-d\TH:i'))
            ->set('endsAt', now()->addDays(3)->format('Y-m-d\TH:i'))
            ->call('save')
            ->assertHasErrors('ends_at');

        $this->assertSame(0, HiringCampaign::query()->count());
    }

    /** A draft offers publishing and disabling, and nothing that makes no sense yet. */
    public function test_a_draft_campaign_offers_only_the_moves_it_can_make(): void
    {
        [$hr, $vacancy] = $this->seat();
        $campaign = app(HiringCampaignService::class)->create($hr, $vacancy);

        Livewire::actingAs($hr)
            ->test(Show::class, ['campaignId' => $campaign->getKey()])
            ->assertSee(__('recruitment-campaign.actions'))
            ->assertSee(__('recruitment-campaign.act.publish'))
            ->assertSee(__('recruitment-campaign.publish_blocked'))
            ->assertSee('data-campaign-action="publish" disabled', false)
            ->assertSee(__('recruitment-campaign.act.disable'))
            ->assertSee('data-recruitment-confirmation-modal="campaign-actions"', false)
            ->assertSee('requireReason: true', false)
            ->assertDontSee('wire:confirm', false)
            ->assertDontSee(__('recruitment-campaign.act.close'))
            ->assertDontSee(__('recruitment-campaign.act.reopen'));
    }

    public function test_publish_action_is_enabled_once_both_assets_are_published(): void
    {
        [$hr, $vacancy] = $this->seat();
        $campaign = app(HiringCampaignService::class)->create($hr, $vacancy);
        $this->prepareCampaignForOpening($campaign, $hr);

        Livewire::actingAs($hr)
            ->test(Show::class, ['campaignId' => $campaign->getKey()])
            ->assertDontSee(__('recruitment-campaign.publish_blocked'))
            ->assertSee('wire:click="act(\'publish\')"', false);
    }

    public function test_close_and_disable_require_a_reason_from_the_screen(): void
    {
        [$hr, $campaign] = $this->openCampaign();

        Livewire::actingAs($hr)
            ->test(Show::class, ['campaignId' => $campaign->getKey()])
            ->call('act', 'close')
            ->assertHasErrors('reason');

        $this->assertSame(Status::Open, (int) $campaign->fresh()->status);

        Livewire::actingAs($hr)
            ->test(Show::class, ['campaignId' => $campaign->getKey()])
            ->call('act', 'disable')
            ->assertHasErrors('reason');

        $this->assertSame(Status::Open, (int) $campaign->fresh()->status);
    }

    public function test_closing_from_the_screen_stops_submissions(): void
    {
        [$hr, $campaign] = $this->openCampaign();

        Livewire::actingAs($hr)
            ->test(Show::class, ['campaignId' => $campaign->getKey()])
            ->set('reason', 'Enough applicants.')
            ->call('act', 'close')
            ->assertHasNoErrors();

        $this->assertSame(Status::Closed, (int) $campaign->fresh()->status);
        $this->assertFalse(app(HiringCampaignService::class)->acceptsSubmissions($campaign->fresh()));
    }

    /**
     * A campaign whose window ran out shows as expired, before any sweep.
     *
     * The screen reads the clock like every other gate, so a list that still
     * said "open" would be quietly lying to whoever is managing it.
     */
    public function test_the_screen_shows_a_lapsed_campaign_as_expired(): void
    {
        [$hr, $campaign] = $this->openCampaign();
        $campaign->forceFill(['ends_at' => now()->subHour()])->save();

        $this->assertSame(Status::Open, (int) $campaign->fresh()->status);

        Livewire::actingAs($hr)
            ->test(Show::class, ['campaignId' => $campaign->fresh()->getKey()])
            ->assertSee(Status::getLocalizedName(Status::Expired)['value']);
    }

    /** A failed move says why and leaves the campaign alone. */
    public function test_publishing_without_an_advert_is_refused_on_the_screen(): void
    {
        [$hr, $vacancy] = $this->seat();
        $campaign = app(HiringCampaignService::class)->create($hr, $vacancy);

        Livewire::actingAs($hr)
            ->test(Show::class, ['campaignId' => $campaign->getKey()])
            ->call('act', 'publish')
            ->assertHasErrors('campaign');

        $this->assertSame(Status::Draft, (int) $campaign->fresh()->status);
    }

    /**
     * Somebody who may only look sees no buttons at all.
     *
     * The managing permission is taken away and nothing else is touched, so
     * the buttons disappearing can only be measuring that.
     */
    public function test_a_viewer_without_manage_sees_no_actions(): void
    {
        [$hr, $campaign] = $this->openCampaign();

        // The permission is taken from the role rather than the person, and
        // from the role rather than a second user, because seeing a campaign
        // at all depends on holding the governorate's HR role. Everything else
        // about this user is unchanged, so the missing buttons can only be
        // measuring the permission.
        Role::findByName($hr->myRole(), 'web')
            ->revokePermissionTo(Permission::findByName('recruitment-campaigns.manage'));
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Livewire::actingAs($hr->fresh())
            ->test(Show::class, ['campaignId' => $campaign->getKey()])
            ->assertSee(__('recruitment-campaign.accepts_submissions'))
            ->assertDontSee(__('recruitment-campaign.act.close'))
            ->assertDontSee(__('recruitment-campaign.act.disable'))
            ->assertDontSee(__('recruitment-campaign.save_window'));
    }

    /** @return array{0: User, 1: Vacancy} */
    private function seat(): array
    {
        ['hr' => $hr, 'request' => $request, 'position' => $position] = $this->workflow();

        $request->forceFill([
            'request_type' => RecruitmentRequestType::FillVacancy,
            'status' => RecruitmentRequestStatus::Applied,
        ])->save();

        $vacancy = Vacancy::query()->create([
            'position_id' => $position->id,
            'created_from_request_id' => $request->id,
            'status' => Vacancy::OPEN,
            'needed_count' => 1,
            'filled_count' => 0,
            'open_at' => now()->subDay(),
        ]);

        return [$hr, $vacancy];
    }

    /** @return array{0: User, 1: HiringCampaign} */
    private function openCampaign(): array
    {
        [$hr, $vacancy] = $this->seat();
        $campaigns = app(HiringCampaignService::class);

        $campaign = $campaigns->create($hr, $vacancy, ['ends_at' => now()->addMonth()->toDateTimeString()]);
        $campaign->forceFill(['status' => Status::Published])->save();
        $campaign = $this->prepareCampaignForOpening($campaign->fresh(), $hr);

        return [$hr, $campaigns->open($hr, $campaign->fresh())];
    }
}
