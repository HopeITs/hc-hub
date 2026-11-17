<?php

use App\Support\Desk\Panels;
use App\Support\Modules\ModuleRegistry;
use Modules\Hr\Support\Desk\HrDestinations;
use Modules\Hr\Support\Desk\HrPanels;
use Modules\Hr\Support\Inbox\DeskAgenda;
use Modules\Hr\Support\Inbox\HrSubjectLinks;

/*
| Modules add their sidebar pages and Desk panels from their own
| ServiceProvider (D10): app/ reads them through the registry and never
| names a module.
*/

test('the HR module registers its pages and Desk cards once, from its own provider', function () {
    $registry = app(ModuleRegistry::class);
    $registry->addDestinations(HrDestinations::class);
    $registry->addDeskPanels(HrPanels::class);

    expect(array_map(get_class(...), $registry->destinationSources()))->toBe([HrDestinations::class])
        ->and(array_map(get_class(...), $registry->deskPanelSources()))->toBe([HrPanels::class])
        ->and($registry->deskPanelSourceFor('hr-news'))->toBeInstanceOf(HrPanels::class)
        ->and($registry->deskPanelSourceFor('needs'))->toBeNull();
});

test('the HR module gives the Desk agenda its rows and the notification links their HR pages', function () {
    $registry = app(ModuleRegistry::class);

    expect(array_map(get_class(...), $registry->deskAgendaSources()))->toBe([DeskAgenda::class])
        ->and($registry->subjectLinkSourceFor('employee'))->toBeInstanceOf(HrSubjectLinks::class)
        ->and($registry->subjectLinkSourceFor('leave_request'))->toBeInstanceOf(HrSubjectLinks::class)
        ->and($registry->subjectLinkSourceFor('contract'))->toBeInstanceOf(HrSubjectLinks::class)
        ->and($registry->subjectLinkSourceFor('task'))->toBeNull();
});

test('a module panel is drawn by the module\'s component, the Desk\'s own panels by theirs', function () {
    $panels = app(Panels::class);

    expect($panels->moduleComponentOf('hr-news'))->toBe('hr::desk.card')
        ->and($panels->moduleComponentOf(HrPanels::Notes))->toBe('hr::desk.notes')
        ->and($panels->moduleComponentOf('needs'))->toBeNull()
        ->and($panels->readyKindOf('hr-news'))->toBe('people');
});
