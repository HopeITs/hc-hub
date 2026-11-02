<?php

namespace Modules\Media\Tests\Feature;

use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Modules\Media\Database\Seeders\MediaDemoSeeder;
use Modules\Media\Livewire\MediaRequests\RequestWizard;
use Modules\Media\Models\MediaAssistantLog;
use Tests\TestCase;

/** What people type to the assistant is kept, so it can be taught the words it misses. */
class AssistantLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->seed(MediaDemoSeeder::class);
    }

    public function test_what_the_assistant_understood_is_recorded(): void
    {
        Livewire::actingAs($this->user('rami'))
            ->test(RequestWizard::class)
            ->call('open', null, 'لازمني شهادات لطلاب الديسك بحلب الأسبوع الجاي');

        $log = MediaAssistantLog::sole();
        $this->assertTrue($log->is_understood);
        $this->assertSame(['001', 'new'], [$log->service, $log->type]);
        $this->assertSame(Program::where('slug', 'DISC')->value('id'), $log->program_id);
        $this->assertSame($this->user('rami')->employee_id, $log->employee_id);
    }

    public function test_a_sentence_it_does_not_understand_is_recorded_as_missed(): void
    {
        Livewire::actingAs($this->user('rami'))
            ->test(RequestWizard::class)
            ->call('open', null, 'بدي شي حلو للعيد');

        $this->assertFalse(MediaAssistantLog::sole()->is_understood);
    }

    public function test_the_final_request_is_linked_and_a_changed_guess_is_flagged(): void
    {
        Livewire::actingAs($this->user('rami'))
            ->test(RequestWizard::class)
            ->call('open', null, 'لازمني شهادات لطلاب الديسك بحلب الأسبوع الجاي')
            ->call('chooseType', '001', 'edit')
            ->set('answers.editWhat', ['names'])
            ->set('title', 'Volunteers')
            ->call('next')
            ->call('next')
            ->call('next')
            ->call('submit')
            ->assertHasNoErrors();

        $log = MediaAssistantLog::sole();
        $this->assertNotNull($log->media_request_id);
        $this->assertSame(['001', 'edit'], [$log->final_service, $log->final_type]);
        $this->assertTrue($log->is_corrected);
    }

    public function test_only_people_with_the_log_see_the_page(): void
    {
        $this->actingAs($this->user('maya'))->get('/media-requests/assistant')->assertOk();
        $this->actingAs($this->user('rami'))->get('/media-requests/assistant')->assertForbidden();
    }

    private function user(string $key): User
    {
        return User::where('email', $key.'@example.test')->firstOrFail();
    }
}
