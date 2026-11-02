<?php

namespace Modules\Media\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Modules\Hr\Models\Employee;
use Modules\Media\Database\Seeders\MediaDemoSeeder;
use Modules\Media\Support\MediaTeam;
use Tests\TestCase;

/** Everyone on the team can take any request; each person's specialty decides what they see first. */
class SpecialtyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->seed(MediaDemoSeeder::class);
    }

    public function test_each_request_type_has_one_field(): void
    {
        $team = app(MediaTeam::class);

        $this->assertSame('design', $team->specialty('001', 'new'));
        $this->assertSame('design', $team->specialty('001', 'print'));
        $this->assertSame('design', $team->specialty('000', 'pack'));
        $this->assertSame('photo', $team->specialty('002', 'shoot'));
        $this->assertSame('photo', $team->specialty('002', 'export'));
        $this->assertSame('video', $team->specialty('003', 'shoot'));
        $this->assertSame('montage', $team->specialty('003', 'export'));
        $this->assertNull($team->specialty('999', 'new'));
    }

    public function test_specialists_are_suggested_first_but_everyone_stays(): void
    {
        $team = app(MediaTeam::class);

        $forVideo = $team->membersFor('video')->pluck('username')->all();
        $this->assertSame('sami', $forVideo[0]);
        $this->assertEqualsCanonicalizing(['maya', 'fadi', 'sami'], $forVideo);

        $this->assertSame('sami', $team->membersFor('montage')->first()->username);
        $this->assertNotSame('sami', $team->membersFor('design')->first()->username);
    }

    public function test_the_board_shows_your_field_first_and_can_show_one_field(): void
    {
        $sami = User::where('email', 'sami@example.test')->firstOrFail();

        $this->actingAs($sami)->get('/media-requests/board')->assertOk()->assertSee(__('media.spec.all'));
        $this->actingAs($sami)->get('/media-requests/board?field=video')->assertOk();
    }

    public function test_the_specialty_comes_from_the_position_title(): void
    {
        $team = app(MediaTeam::class);

        $this->assertSame(['photo'], $team->skillsFromTitle('Communication team member / Photographer'));
        $this->assertSame(['video'], $team->skillsFromTitle('Communication team member / Videographer'));   // edits only when it says Editor
        $this->assertSame(['video', 'montage'], $team->skillsFromTitle('Videographer / Editor'));
        $this->assertSame(['montage'], $team->skillsFromTitle('Video Editor'));
        $this->assertSame(['design'], $team->skillsFromTitle('Communication team member / Designer'));
        $this->assertSame(['photo'], $team->skillsFromTitle('عضو فريق التواصل / مصور'));
        $this->assertSame(['video'], $team->skillsFromTitle('مصور فيديو'));
        $this->assertSame([], $team->skillsFromTitle('Communication team member'));
        $this->assertSame([], $team->skillsFromTitle(''));
    }

    public function test_second_skills_and_extras_and_part_time(): void
    {
        $team = app(MediaTeam::class);
        // Title and part-time hours come from the HR position and contract.
        $e = MediaDemoSeeder::person('vera', 'Vera', team: 'member', title: 'Video Editor / Videographer / Photographer (on need) / Animation design (on need) / Local print', hours: '10:00-14:00', account: false);

        $this->assertSame(['video', 'montage'], $team->skillsOf($e));
        $this->assertSame(['photo'], $team->extraSkillsOf($e));              // animation design is not design
        $this->assertSame(['print', 'animation'], $team->extrasOf($e));
        $this->assertSame(1, $team->skillRank($e, 'photo'));
        $this->assertSame(0.5, $team->dayShare($e));
        $this->assertSame('10:00-14:00', $team->workHoursOf($e));
        $this->assertSame(1.0, $team->dayShare(Employee::where('username', 'sami')->sole()));
        $e->update(['is_active' => false]);
        $team->flush();

        // Someone who photographs on need comes after the photographers, before the rest.
        $this->retitle('sami', 'Videographer / Photographer (on need)');
        $team->flush();
        $this->assertSame(['maya', 'sami', 'fadi'], $team->membersFor('photo')->pluck('username')->all());
    }

    /** In the hub the position is HR's: renaming it in HR changes the specialty (media-request had a People page for this). */
    public function test_the_hr_position_title_sets_the_specialty(): void
    {
        $fadi = Employee::where('username', 'fadi')->firstOrFail();
        $this->assertSame(['design'], app(MediaTeam::class)->skillsOf($fadi));

        $this->retitle('fadi', 'Communication team member / Photographer');
        app(MediaTeam::class)->flush();

        $this->assertSame(['photo'], app(MediaTeam::class)->skillsOf($fadi->fresh()));
    }

    /** Rename the person's media position in HR. */
    private function retitle(string $username, string $title): void
    {
        $employee = Employee::where('username', $username)->sole();
        \Modules\Hr\Models\Position::whereIn('id', $employee->contracts()->pluck('position_id'))->update(['title_en' => $title]);
    }
}
