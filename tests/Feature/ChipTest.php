<?php

use App\Enums\StatusTone;
use App\Support\FluxMarkup;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Modules\Tracking\Enums\ResultType;
use Modules\Tracking\Enums\TaskStatus;

beforeEach(function () {
    View::share('errors', new ViewErrorBag);
});

test('the status chip renders each brand tone with its icon and label', function (StatusTone $tone) {
    $html = Blade::render('<x-ui.chip :tone="$tone">Label</x-ui.chip>', ['tone' => $tone->value]);

    expect($html)->toContain('ui-chip')
        ->and($html)->toContain($tone->badgeClasses())
        ->and($html)->toContain('data-tone="'.$tone->value.'"')
        ->and($html)->toContain('<svg')
        ->and($html)->toContain('Label');
})->with(StatusTone::cases());

test('the tag renders each brand tone without an icon unless one is given', function (StatusTone $tone) {
    $plain = Blade::render('<x-ui.tag :tone="$tone">Label</x-ui.tag>', ['tone' => $tone]);
    $withIcon = Blade::render('<x-ui.tag :tone="$tone" icon="flag">Label</x-ui.tag>', ['tone' => $tone]);

    expect($plain)->toContain('ui-tag')
        ->and($plain)->toContain($tone->badgeClasses())
        ->and($plain)->toContain('data-tone="'.$tone->value.'"')
        ->and($plain)->not->toContain('<svg')
        ->and($withIcon)->toContain('<svg');
})->with(StatusTone::cases());

test('an unknown tone falls back to neutral', function () {
    $html = Blade::render('<x-ui.chip tone="purple">Label</x-ui.chip>');

    expect($html)->toContain('data-tone="neutral"')
        ->and($html)->toContain(StatusTone::Neutral->badgeClasses());
});

test('the enum badge renders a status as a chip and any other enum as a tag', function () {
    $status = Blade::render('<x-ui.enum-badge :case="$case" />', ['case' => TaskStatus::Done]);
    $other = Blade::render('<x-ui.enum-badge :case="$case" />', ['case' => ResultType::cases()[0]]);

    expect($status)->toContain('ui-chip')->not->toContain('ui-tag')
        ->and($other)->toContain('ui-tag')->not->toContain('ui-chip');
});

test('the tone badge is a chip with an icon and a tag without one', function () {
    $chip = Blade::render('<x-ui.tone-badge tone="warn" icon="clock">Late</x-ui.tone-badge>');
    $tag = Blade::render('<x-ui.tone-badge tone="warn">Late</x-ui.tone-badge>');

    expect($chip)->toContain('ui-chip')->toContain('data-tone="warn"')
        ->and($tag)->toContain('ui-tag')->toContain('data-tone="warn"');
});

test('the hold chip renders through the warn status chip', function () {
    $html = Blade::render('<x-ui.hold-chip />');

    expect($html)->toContain('ui-chip')
        ->and($html)->toContain('data-tone="warn"')
        ->and($html)->toContain('data-test="on-hold-chip"')
        ->and($html)->toContain(e(__('proposals.on_hold_chip')));
});

test('the task status pill is a chip and its quiet form a tag', function () {
    $loud = (string) FluxMarkup::statusPill(TaskStatus::InProgress);
    $quiet = (string) FluxMarkup::statusPill(TaskStatus::InProgress, quiet: true);

    expect($loud)->toContain('ui-chip')
        ->and($quiet)->toContain('ui-tag')
        ->and($quiet)->toContain(TaskStatus::InProgress->tone()->badgeClasses());
});

test('the shapes are defined once and the desk pills read the same tone colours', function () {
    $chipCss = file_get_contents(resource_path('css/chip.css'));
    $deskCss = file_get_contents(resource_path('css/desk.css'));

    expect($chipCss)->toContain('@utility ui-chip')
        ->and($chipCss)->toContain('@utility ui-tag')
        ->and($deskCss)->toContain('@apply ui-chip')
        ->and($deskCss)->toContain('@apply ui-tag');

    foreach (StatusTone::cases() as $tone) {
        expect($deskCss)->toContain($tone->badgeClasses());
    }
});
