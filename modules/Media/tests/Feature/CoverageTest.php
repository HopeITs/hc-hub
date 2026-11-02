<?php

namespace Modules\Media\Tests\Feature;

use App\Models\City;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Modules\Media\Database\Seeders\MediaDemoSeeder;
use Modules\Media\Enums\MediaRequestStatus as S;
use Modules\Media\Models\MediaRequest;
use Modules\Media\Services\MediaRequestWorkflow;
use Modules\Media\Support\MediaTeam;
use Tests\TestCase;

/** Design and editing are for everyone; a shoot is done by the team of the place, or as an exception. */
class CoverageTest extends TestCase
{
    use RefreshDatabase;

    private MediaRequestWorkflow $w;

    private MediaTeam $team;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->seed(MediaDemoSeeder::class);
        $this->w = app(MediaRequestWorkflow::class);
        $this->team = app(MediaTeam::class);
    }

    public function test_tartus_is_shot_by_the_latakia_team_and_others_go_only_when_sent(): void
    {
        $lat = $this->member('hadi', 'LAT');
        $r = $this->shoot('TAR');

        $this->assertSame('hadi', $this->team->membersFor('photo', $r)->first()->username);
        $this->assertTrue(Gate::forUser($lat)->allows('claim', $r));
        $this->assertFalse(Gate::forUser($this->user('fadi'))->allows('claim', $r));   // Aleppo

        // The media manager may still send someone from Aleppo.
        $this->w->assign($this->user('maya'), $r, $this->user('fadi')->employee_id);
        $this->assertSame(S::Assigned, $r->fresh()->status);

        $html = $this->actingAs($this->user('maya'))->get('/media-requests/'.$this->shoot('TAR')->id)->getContent();
        $this->assertStringContainsString(__('media.cov.other'), $html);
        $this->assertStringContainsString(__('media.cov.area'), $html);
    }

    public function test_design_is_done_by_anyone_wherever_it_is(): void
    {
        $r = $this->w->submit($this->user('lina'), [
            'service' => '001', 'type' => 'edit', 'program_id' => Program::where('slug', 'DISC')->value('id'),
            'city_id' => City::where('slug', 'TAR')->value('id'), 'title' => 'Edit',
            'needed_on' => \Modules\Media\Support\WorkCalendar::shift(today(), 8)->toDateString(), 'answers' => ['editWhat' => ['text']],
        ]);
        $this->w->approveMedia($this->user('maya'), $r->fresh(), 'normal');

        $this->assertFalse($this->team->onSite($r->fresh()));
        $this->assertTrue(Gate::forUser($this->user('fadi'))->allows('claim', $r->fresh()));
    }

    public function test_damascus_has_its_own_team_first_then_homs_or_latakia(): void
    {
        $this->member('hadi', 'LAT');
        $this->member('jad', 'HMS');
        $this->member('dana', 'DMS');
        $r = $this->shoot('DMS');

        $order = $this->team->membersFor('photo', $r)->pluck('username')->take(3)->all();
        $this->assertSame(['dana', 'jad', 'hadi'], $order);
        $this->assertSame(['ALP'], $this->team->areaTeams($this->shoot('IDL')));
    }

    public function test_al_jazira_is_covered_by_a_service_agreement(): void
    {
        $r = $this->shoot('HSK');

        $this->assertTrue($this->team->underAgreement($r));
        $this->actingAs($this->user('maya'))->get('/media-requests/'.$r->id)
            ->assertSee(__('media.cov.agreement', ['city' => 'Hasakah']));
    }

    private function shoot(string $city): MediaRequest
    {
        $r = $this->w->submit($this->user('lina'), [
            'service' => '002', 'type' => 'shoot', 'program_id' => Program::where('slug', 'DISC')->value('id'),
            'city_id' => City::where('slug', $city)->value('id'), 'title' => 'Shoot',
            'needed_on' => \Modules\Media\Support\WorkCalendar::shift(today(), 10)->toDateString(),
            'answers' => ['coverage' => 'event', 'eventDate' => \Modules\Media\Support\WorkCalendar::shift(today(), 8)->toDateString(), 'place' => 'Hall', 'children' => 'no', 'usage' => ['social']],
        ]);
        $this->w->approveMedia($this->user('maya'), $r->fresh(), 'normal');

        return $r->fresh(['city']);
    }

    private function member(string $key, string $city): User
    {
        MediaDemoSeeder::person($key, ucfirst($key), $key, team: 'member', title: 'Communication team member / Photographer', city: $city);
        $this->team->flush();

        return User::where('email', $key.'@example.test')->sole();
    }

    private function user(string $key): User
    {
        return User::where('email', $key.'@example.test')->firstOrFail();
    }
}
