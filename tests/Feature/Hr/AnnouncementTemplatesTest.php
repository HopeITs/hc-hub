<?php

use Livewire\Livewire;
use Modules\Core\Models\User;
use Modules\Hr\Models\Employee;
use Modules\Hr\Support\Announcements\AdministrativeChange;
use Modules\Hr\Support\Announcements\AnnouncementTemplates;
use Modules\Hr\Support\Announcements\HolidayClosure;
use Modules\Hr\Support\Announcements\VolunteerResignation;
use Modules\Hr\Support\Announcements\WelcomeNewcomer;
use Modules\Hr\Support\Settings\HrAccess;
use Spatie\Permission\Models\Permission;

/*
| The ready-made announcements: each one reads exactly as the office writes it
| from the fields, its grammar follows the gender chosen, and only someone who
| may use message templates opens the composer.
*/

beforeEach(function () {
    HrAccess::flush();
});

function announcementUser(bool $mayUse = true): User
{
    $user = User::factory()->create();

    if ($mayUse) {
        $user->givePermissionTo(Permission::findOrCreate('message-templates.use', 'web'));
    }

    return $user->fresh();
}

test('the welcome reads as the office writes it', function () {
    $message = (new WelcomeNewcomer)->render([
        'gender' => 'm',
        'name' => 'جان مغامز',
        'position' => 'مستشار (Consultant) لمشاريع التمكين في المؤسسة',
        'center' => 'Christian Hope Center',
        'direction' => 'تعزيز قيمة التمكين وتطوير برامجها',
        'projects' => 'الـ DISC, LEAP, Children University',
        'expertise' => 'التمكين',
    ]);

    expect($message['body'])->toBe(
        "الأعزاء،\nنهاركم سعيد،\n\n"
        .'انسجامًا مع توجهات المؤسسة في تعزيز قيمة التمكين وتطوير برامجها، يسرّنا إعلامكم بانضمام السيد جان مغامز إلى عائلة Christian Hope Center بمنصب مستشار (Consultant) لمشاريع التمكين في المؤسسة، والتي تشمل الـ DISC, LEAP, Children University.'
        ."\n\n".'نرحّب به بيننا، ونتمنى له كل التوفيق في مهامه الجديدة. نحن على ثقة بأن خبرته الطويلة في مجال التمكين ستشكّل إضافة نوعيّة تسهم في دعم هذه المشاريع وتعزيز أثرها.'
        ."\n\nمع خالص التقدير والمحبة،"
    );
    expect($message['subject'])->toBe('ترحيب بانضمام جان مغامز إلى عائلة Christian Hope Center');
});

test('the welcome follows a woman and drops the optional parts', function () {
    $body = (new WelcomeNewcomer)->render([
        'gender' => 'f', 'name' => 'رين', 'position' => 'منسقة', 'center' => 'Christian Hope Center', 'direction' => 'التمكين',
    ])['body'];

    expect($body)
        ->toContain('بانضمام السيدة رين')
        ->toContain('نرحّب بها بيننا، ونتمنى لها كل التوفيق في مهامها الجديدة')
        ->toContain('بأن خبرتها ستشكّل')
        ->toContain('دعم عمل المؤسسة وتعزيز أثره.')
        ->not->toContain('والتي تشمل');
});

test('a missing field shows its name and is listed', function () {
    $template = new WelcomeNewcomer;
    $values = $template->defaults();

    expect($template->render($values)['body'])->toContain('«الاسم»');
    expect($template->missing($values))->toContain($template->fieldLabel('name'));
});

test('the resignation request writes the weekday and greets the specialist', function () {
    $message = (new VolunteerResignation)->render([
        'gender' => 'f', 'name' => 'راوية العنيني', 'volunteer_id' => '483', 'date' => '2026-06-30',
        'greeting_gender' => 'f', 'greeting_name' => 'مي',
    ]);

    expect($message['body'])->toBe(
        "العزيزة مي،\nأتمنى ان تكوني بخير،،،\n\n"
        ."قدمت الزميلة راوية العنيني استقالتها يوم الثلاثاء الواقع في 30/6/2026.\n\n"
        ."وبناء على ذلك أرجو منك إيقاف الإيميل الخاص بها علماً أن رقم الـID هو 483.\n\n"
        ."نهارك سعيد\nتحياتي"
    );
    expect((new VolunteerResignation)->recipients())->toBe(['to' => 'mai.youssef@hcsyria.org', 'cc' => 'judy.dadaghlian@hcsyria.org']);
});

test('the administrative change thanks by first name and names the replacement', function () {
    $body = (new AdministrativeChange)->render([
        'audience' => 'متطوعي مركز الرجاء المسيحي', 'title' => 'السيد', 'name' => 'رامي تارك',
        'position' => 'مدير مشروع', 'project' => 'ADISC', 'date' => '2027-01-01',
        'has_replacement' => true, 'replacement_title' => 'الآنسة', 'replacement_name' => 'رين شنكجي', 'replacement_date' => '2027-01-01',
    ])['body'];

    expect($body)
        ->toContain('نود إعلامكم بأن السيد رامي تارك سينهي تطوعه في المؤسسة بمنصب مدير مشروع ADISC، وسيكون ذلك نافذاً اعتباراً من 01/01/2027.')
        ->toContain('بالشكر الجزيل للسيد رامي على الجهود التي بذلها')
        ->toContain('بتكليف الآنسة رين شنكجي بمهام مدير مشروع ADISC اعتباراً من 01/01/2027، ونتمنى لها التوفيق والنجاح في مسؤولياتها.');

    $alone = (new AdministrativeChange)->render(['title' => 'السيدة', 'name' => 'هبة', 'position' => 'منسقة', 'date' => '2027-01-01', 'has_replacement' => false])['body'];

    expect($alone)->toContain('ستنهي تطوعها')->not->toContain('بتكليف');
});

test('the holiday return follows its last day with the weekday', function () {
    $template = new HolidayClosure;
    $values = $template->updated('end', ['start' => '2026-12-23', 'end' => '2027-01-11', 'resume' => '', 'office' => 'مكتب الإدارة العامة']);

    expect($values['resume'])->toBe('2027-01-12');
    expect($template->render($values)['body'])->toContain('ابتداءً من 23/12/2026 ولغاية 11/01/2027، على أن نستأنف الدوام يوم الثلاثاء الموافق 12/01/2027.');
    expect($template->problems($values))->toBe([]);
    expect($template->problems([...$values, 'resume' => '2027-01-10']))->toHaveCount(1);
});

test('the mailto carries every recipient and skips what is not an address', function () {
    $mail = AnnouncementTemplates::mailto('team@hcsyria.org', 'fredy.youssef@hcsyria.org; nonsense', 'عطلة', "سطر\nسطر");

    expect($mail['trimmed'])->toBeFalse();
    expect($mail['url'])
        ->toStartWith('mailto:team@hcsyria.org?cc=fredy.youssef@hcsyria.org&subject=')
        ->toContain('body='.rawurlencode("سطر\r\nسطر"));
    expect(AnnouncementTemplates::mailto('', '', 's', 'b'))->toBeNull();
});

test('a link too long for a mail program drops its text and says so', function () {
    $holiday = new HolidayClosure;
    $message = $holiday->render([...$holiday->defaults(), 'start' => '2026-12-23', 'end' => '2027-01-11', 'resume' => '2027-01-12']);

    $mail = AnnouncementTemplates::mailto('team@hcsyria.org', 'fredy.youssef@hcsyria.org', $message['subject'], $message['body']);

    expect($mail['trimmed'])->toBeTrue();
    expect(strlen($mail['url']))->toBeLessThanOrEqual(AnnouncementTemplates::MAILTO_LIMIT);
    expect($mail['url'])->not->toContain('body=')->toContain('subject=');
});

test('a long email comes as a message file with the whole text right to left', function () {
    $holiday = new HolidayClosure;
    $message = $holiday->render([...$holiday->defaults(), 'start' => '2026-12-23', 'end' => '2027-01-11', 'resume' => '2027-01-12']);

    $eml = AnnouncementTemplates::eml('team@hcsyria.org', 'fredy.youssef@hcsyria.org', $message['subject'], $message['body']);

    expect($eml)
        ->toStartWith("X-Unsent: 1\r\nTo: team@hcsyria.org\r\nCc: fredy.youssef@hcsyria.org\r\nSubject: =?UTF-8?B?")
        ->toContain('charset=UTF-8');

    [$head, $encoded] = explode("\r\n\r\n", $eml, 2);
    $html = base64_decode($encoded);

    expect($html)
        ->toContain('dir="rtl"')
        ->toContain('font-family:\'Somar Sans\'')
        ->toContain('مكتب الإدارة العامة سيُعطل بمناسبة الأعياد ابتداءً من 23/12/2026');
    expect(substr_count($html, '<p dir="rtl"'))->toBe(substr_count($html, '<p '));
    expect(mb_decode_mimeheader($head))->toContain('Subject: '.$message['subject']);
    expect(AnnouncementTemplates::eml('', '', 's', 'b'))->toBeNull();
});

test('the composer downloads the message file for whoever may use it', function () {
    Livewire::actingAs(announcementUser())->test('hr::message-templates.announcements')
        ->call('compose', 'holiday_closure')
        ->call('downloadEml')
        ->assertFileDownloaded('holiday_closure.eml');

    Livewire::actingAs(announcementUser(false))->test('hr::message-templates.announcements')
        ->call('downloadEml')
        ->assertForbidden();
});

test('the signature ends every email and survives choosing another template', function () {
    Livewire::actingAs(announcementUser())->test('hr::message-templates.announcements')
        ->call('compose', 'holiday_closure')
        ->set('signature', "مع التحية\nقسم الموارد البشرية")
        ->assertSee('قسم الموارد البشرية')
        ->call('downloadEml')
        ->assertFileDownloaded('holiday_closure.eml')
        ->call('back')
        ->call('choose', 'welcome')
        ->assertSet('signature', "مع التحية\nقسم الموارد البشرية");
});

test('only whoever may use templates opens the composer', function () {
    Livewire::actingAs(announcementUser(false))->test('hr::message-templates.announcements')
        ->dispatch('announcement-compose')
        ->assertForbidden();

    Livewire::actingAs(announcementUser())->test('hr::message-templates.announcements')
        ->dispatch('announcement-compose', template: 'welcome', prefill: ['name' => 'جان مغامز', 'not_a_field' => 'x'])
        ->assertSet('open', true)
        ->assertSet('template', 'welcome')
        ->assertSet('values.name', 'جان مغامز')
        ->assertSet('to', 'team@hcsyria.org')
        ->assertSee('ترحيب بانضمام جان مغامز')
        ->set('values.end', 'ignored')
        ->assertSet('values', fn (array $values): bool => ! array_key_exists('not_a_field', $values) && ! array_key_exists('end', $values));
});

test('the holiday composer moves the return day', function () {
    Livewire::actingAs(announcementUser())->test('hr::message-templates.announcements')
        ->call('compose', 'holiday_closure')
        ->set('values.end', '2027-01-11')
        ->assertSet('values.resume', '2027-01-12')
        ->assertSet('cc', 'fredy.youssef@hcsyria.org');
});

test('a profile offers the announcements about one person, with the Arabic name in', function () {
    $employee = Employee::factory()->create(['first_name_ar' => 'جان', 'last_name_ar' => 'مغامز']);

    $this->actingAs(announcementUser());

    $this->blade('<x-hr::employees.message-template-menu :employee="$employee" />', ['employee' => $employee])
        ->assertSee('data-announcement-link="welcome"', false)
        ->assertSee('data-announcement-link="volunteer_resignation"', false)
        ->assertSee('جان', false);

    $this->actingAs(announcementUser(false));

    $this->blade('<x-hr::employees.message-template-menu :employee="$employee" />', ['employee' => $employee])
        ->assertDontSee('data-announcement-link', false);
});
