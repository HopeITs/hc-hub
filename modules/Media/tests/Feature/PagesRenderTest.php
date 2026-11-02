<?php

namespace Modules\Media\Tests\Feature;

use App\Models\City;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Modules\Media\Database\Seeders\MediaDemoSeeder;
use Modules\Media\Services\MediaRequestWorkflow;
use Tests\TestCase;

/**
 * Opens every screen as the people who use it. Catches a page that no longer
 * renders (a Blade error, a missing translation key, a broken relation) that
 * the workflow tests would not notice.
 */
class PagesRenderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->seed(MediaDemoSeeder::class);
    }

    public function test_every_screen_renders_for_the_people_who_use_it(): void
    {
        $request = app(MediaRequestWorkflow::class)->submit($this->user('rami'), [
            'service' => '001', 'type' => 'new',
            'program_id' => Program::where('slug', 'DISC')->value('id'),
            'city_id' => City::where('slug', 'ALP')->value('id'),
            'title' => 'Volunteers', 'needed_on' => \Modules\Media\Support\WorkCalendar::shift(today(), 8)->toDateString(),
            'answers' => ['format' => 'cert', 'certNames' => 'blank', 'langs' => ['ar'], 'alsoPrint' => 'no'],
        ]);

        $pages = [
            'rami' => ['/media-requests/hc-pack', '/media-requests/home?open='.$request->id],
            'lina' => ['/media-requests', '/media-requests/approvals', '/media-requests/'.$request->id],
            'maya' => ['/media-requests', '/media-requests/board', '/media-requests/log', '/media-requests/'.$request->id],
            'fadi' => ['/media-requests', '/media-requests/board', '/media-requests/'.$request->id],
            'admin' => ['/media-requests', '/media-requests/board', '/media-requests/log', '/media-requests/approvals'],
        ];

        foreach ($pages as $who => $urls) {
            foreach ($urls as $url) {
                foreach (['ar', 'en'] as $locale) {
                    app()->setLocale($locale);
                    $this->actingAs($this->user($who))
                        ->get($url)
                        ->assertOk()
                        ->assertSee($locale === 'ar' ? 'dir="rtl"' : 'dir="ltr"', false);
                }
            }
        }
    }

    public function test_people_who_only_ask_for_work_stay_on_the_home_screen(): void
    {
        $request = app(MediaRequestWorkflow::class)->submit($this->user('rami'), [
            'service' => '001', 'type' => 'new',
            'program_id' => Program::where('slug', 'DISC')->value('id'),
            'city_id' => City::where('slug', 'ALP')->value('id'),
            'title' => 'Volunteers', 'needed_on' => \Modules\Media\Support\WorkCalendar::shift(today(), 8)->toDateString(),
            'answers' => ['format' => 'cert', 'certNames' => 'blank', 'langs' => ['ar'], 'alsoPrint' => 'no'],
        ]);
        $rami = $this->user('rami');

        $this->actingAs($rami)->get('/media-requests')->assertRedirect('/media-requests/home');
        $this->actingAs($rami)->get('/media-requests/'.$request->id)->assertRedirect('/media-requests/home?open='.$request->id);

        // The request opens in the panel; their requests and notifications are cards; no page links.
        $this->actingAs($rami)->get('/media-requests/home?open='.$request->id)
            ->assertOk()
            ->assertSee('reqpanel', false)
            ->assertSee($request->reference)
            ->assertSee(__('media.dash.card.mine'))
            ->assertDontSee('href="'.route('media-requests.index').'"', false);

        // The team still has its pages.
        $this->actingAs($this->user('maya'))->get('/media-requests/'.$request->id)->assertOk();
    }

    public function test_people_outside_a_screen_are_kept_out(): void
    {
        $this->actingAs($this->user('rami'))->get('/media-requests/board')->assertForbidden();
        $this->actingAs($this->user('rami'))->get('/media-requests/log')->assertForbidden();
    }

    private function user(string $key): User
    {
        return User::where('email', $key.'@example.test')->firstOrFail();
    }
}
