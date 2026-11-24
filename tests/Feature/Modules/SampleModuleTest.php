<?php

use App\Notifications\SubjectLinks;
use App\Support\Desk\Destinations;
use App\Support\Desk\Panels;
use App\Support\Metrics\OrgMetrics;
use App\Support\Modules\Contracts\CalendarLayerSource;
use App\Support\Modules\Contracts\DeskAgendaSource;
use App\Support\Modules\Contracts\DeskPanelSource;
use App\Support\Modules\Contracts\DestinationSource;
use App\Support\Modules\Contracts\EmployeeTabSource;
use App\Support\Modules\Contracts\MetricSource;
use App\Support\Modules\Contracts\PickerScopeSource;
use App\Support\Modules\Contracts\ScheduleSource;
use App\Support\Modules\Contracts\SearchSource;
use App\Support\Modules\Contracts\SubjectLinkSource;
use App\Support\Modules\Contracts\TimelineSource;
use App\Support\Modules\Contracts\WorkItemSource;
use App\Support\Modules\ModuleRegistry;
use App\Support\Timeline\EmployeeTimeline;
use Illuminate\Console\Scheduling\Schedule;
use Livewire\Livewire;
use Modules\Core\Models\User;
use Modules\Hr\Models\Employee;
use Tests\Fixtures\Modules\Sample\SampleAgenda;
use Tests\Fixtures\Modules\Sample\SampleCalendarLayer;
use Tests\Fixtures\Modules\Sample\SampleDestinations;
use Tests\Fixtures\Modules\Sample\SampleEmployeeTab;
use Tests\Fixtures\Modules\Sample\SampleMetrics;
use Tests\Fixtures\Modules\Sample\SamplePanels;
use Tests\Fixtures\Modules\Sample\SamplePickerScopes;
use Tests\Fixtures\Modules\Sample\SampleSchedule;
use Tests\Fixtures\Modules\Sample\SampleSearch;
use Tests\Fixtures\Modules\Sample\SampleServiceProvider;
use Tests\Fixtures\Modules\Sample\SampleSubjectLinks;
use Tests\Fixtures\Modules\Sample\SampleTimeline;
use Tests\Fixtures\Modules\Sample\SampleWorkItems;

/*
| Roadmap 5.2: a module that lives only under tests/ registers every module
| contract from its own provider, so a new module needs no edit in app/ or in
| another module. The fixture is tests/Fixtures/Modules/Sample.
*/

beforeEach(function (): void {
    $this->app->register(SampleServiceProvider::class);
    // The app has finished booting, so the router's name index is rebuilt the way its own booted hook does.
    $this->app['router']->getRoutes()->refreshNameLookups();
    $this->registry = app(ModuleRegistry::class);
    $this->viewer = User::factory()->create();
});

/**
 * Each contract, the registry reader that returns its sources, and the fixture class that must be among them.
 *
 * @return array<class-string, array{0: string, 1: class-string}>
 */
function sampleModuleCoverage(): array
{
    return [
        CalendarLayerSource::class => ['calendarLayerSources', SampleCalendarLayer::class],
        DeskAgendaSource::class => ['deskAgendaSources', SampleAgenda::class],
        DeskPanelSource::class => ['deskPanelSources', SamplePanels::class],
        DestinationSource::class => ['destinationSources', SampleDestinations::class],
        EmployeeTabSource::class => ['employeeTabSources', SampleEmployeeTab::class],
        MetricSource::class => ['metricSources', SampleMetrics::class],
        PickerScopeSource::class => ['pickerScopeSourceFor', SamplePickerScopes::class],
        ScheduleSource::class => ['scheduleSources', SampleSchedule::class],
        SearchSource::class => ['searchSources', SampleSearch::class],
        SubjectLinkSource::class => ['subjectLinkSourceFor', SampleSubjectLinks::class],
        TimelineSource::class => ['timelineSources', SampleTimeline::class],
        WorkItemSource::class => ['workItemSources', SampleWorkItems::class],
    ];
}

test('the fixture covers every contract in app/Support/Modules/Contracts, so a new one cannot be forgotten', function () {
    $contracts = array_map(
        fn (string $file): string => 'App\\Support\\Modules\\Contracts\\'.basename($file, '.php'),
        glob(base_path('app/Support/Modules/Contracts/*.php')),
    );
    $covered = array_keys(sampleModuleCoverage());
    sort($contracts);
    sort($covered);

    expect($covered)->toBe($contracts);

    foreach (sampleModuleCoverage() as $contract => [, $fixture]) {
        expect(new ReflectionClass($fixture))->isInterface()->toBeFalse()
            ->and(is_subclass_of($fixture, $contract))->toBeTrue("{$fixture} must implement {$contract}")
            ->and(str_starts_with($fixture, 'Tests\\Fixtures\\Modules\\Sample\\'))->toBeTrue();
    }
});

test('every extension point of the registry returns what the fixture contributed', function () {
    $extensionPoints = collect((new ReflectionClass(ModuleRegistry::class))->getMethods(ReflectionMethod::IS_PUBLIC))
        ->map->getName()
        ->filter(fn (string $name): bool => str_starts_with($name, 'add'))
        ->values();

    // One registration method per contract: a new add*() without a contract (or the reverse) fails here.
    expect($extensionPoints)->toHaveCount(count(sampleModuleCoverage()));

    foreach (sampleModuleCoverage() as [$reader, $fixture]) {
        $read = match ($reader) {
            'subjectLinkSourceFor' => [$this->registry->subjectLinkSourceFor(SampleSubjectLinks::Type)],
            'pickerScopeSourceFor' => [$this->registry->pickerScopeSourceFor(SamplePickerScopes::Scope)],
            default => $this->registry->{$reader}(),
        };

        expect(array_map(get_class(...), $read))->toContain($fixture);
    }
});

test('the fixture registered once, however often its provider boots', function () {
    $this->app->register(SampleServiceProvider::class, force: true);

    expect(array_count_values(array_map(get_class(...), $this->registry->destinationSources()))[SampleDestinations::class])->toBe(1);
});

test('the sidebar and Ask offer the module page to someone the module lets in', function () {
    $destinations = app(Destinations::class);

    expect($destinations->allows($this->viewer, 'sample.index'))->toBeTrue()
        ->and($destinations->find('sample.index'))->toMatchArray(['route' => 'sample.index', 'group' => 'workspace', 'label' => 'Sample module'])
        ->and(array_column(collect($destinations->sidebarFor($this->viewer))->firstWhere('group', 'workspace')['items'], 'route'))
        ->toContain('sample.index');
});

test('the Desk draws the module panel with the module component and offers it to the viewer', function () {
    $panels = app(Panels::class);

    expect($panels->moduleComponentOf(SamplePanels::Panel))->toBe('sample::desk.card')
        ->and($panels->readyKindOf(SamplePanels::Panel))->toBe('people')
        ->and($panels->iconOf(SamplePanels::Panel))->toBe('grid')
        ->and($this->registry->deskPanelSourceFor('sample-anything'))->toBeInstanceOf(SamplePanels::class)
        ->and($this->registry->deskPanelSourceFor('needs'))->toBeNull()
        ->and($panels->availableTo($this->viewer))->toHaveKey(SamplePanels::Panel);
});

test('the Desk agenda card lists the module agenda row', function () {
    $this->travelTo('2026-03-02 09:00:00');
    $me = userWithRole('Managers', insertHrEmployee());

    $card = Livewire::actingAs($me)->test('desk.panels.coming', ['size' => 'L']);

    expect($card->instance()->events->pluck('title')->all())->toContain('Sample agenda row');
    $card->assertSee('Sample agenda row');
});

test('the calendar page draws the module layer for someone the layer lets in', function () {
    $this->travelTo('2026-03-02 09:00:00');
    $viewer = User::factory()->role('General Manager')->create(['employee_id' => Employee::factory()->create()->id]);

    Livewire::actingAs($viewer)
        ->test('tracking::calendar.index')
        ->set('month', '2026-03')
        ->assertSee('Sample day')
        ->assertSeeHtml('data-test="calendar-layer-sample-sample-1-2026-03-01"');
});

test('the Waiting on me page lists the module work items', function () {
    $page = Livewire::actingAs($this->viewer)->test('pages::waiting.index')->assertOk();
    $sample = collect($page->instance()->sources)->firstWhere('key', 'sample');

    expect($sample)->toMatchArray(['label' => 'Sample work', 'count' => 1])
        ->and($sample['items'][0]['title'])->toBe('Sample item');
    $page->assertSee('Sample item');
});

test('Ask finds module records through the module search', function () {
    $results = Livewire::actingAs($this->viewer)->test('desk.ask')->set('query', 'sample thing')->instance()->results;
    $group = collect($results['groups'])->firstWhere('source', 'sample');

    expect($group['name'])->toBe('Sample records')
        ->and($group['items'][0])->toMatchArray(['label' => 'Sample thing', 'href' => '/sample']);
});

test('the employee profile offers the module tab, and the timeline gets its entries', function () {
    $employee = Employee::factory()->create();
    $viewer = User::factory()->role('General Manager')->create(['employee_id' => $employee->id]);
    $page = Livewire::actingAs($viewer)->test('hr::employees.show')->assertOk();

    expect(array_keys($page->instance()->moduleTabs()))->toContain('sample')
        ->and($page->instance()->visibleTabs())->toContain('sample')
        ->and(collect(app(EmployeeTimeline::class)->entries($this->viewer, 1))->firstWhere('source', 'sample'))
        ->toMatchArray(['title' => 'Sample entry', 'label' => 'Sample']);
});

test('the organisation numbers include the module number', function () {
    $metrics = app(OrgMetrics::class);

    expect($metrics->has(SampleMetrics::Key))->toBeTrue()
        ->and($metrics->value(SampleMetrics::Key))->toBe(7)
        ->and($metrics->label(SampleMetrics::Key))->toBe('Sample count');
});

test('a notification about a module record leads to the module page', function () {
    expect(app(SubjectLinks::class)->routeFor(SampleSubjectLinks::Type, 5))->toBe(route('sample.index', ['item' => 5]))
        ->and(app(SubjectLinks::class)->routeFor('unknown_type', 5))->toBeNull();
});

test('the module schedule reaches the real scheduler', function () {
    $names = collect(app(Schedule::class)->events())->pluck('description')->all();

    expect($names)->toContain(SampleSchedule::Name);
});

test('the module declares a permission and a prefixed route of its own', function () {
    expect($this->viewer->can(SampleServiceProvider::Ability))->toBeTrue()
        ->and(route('sample.index', absolute: false))->toBe('/sample');

    $this->get('/sample')->assertOk()->assertSee('sample module');
});

/*
| Known gaps: the full list lives in docs/hub-unification/04-adding-a-module.md
| section 10. The register() call in beforeEach stands in for the module's
| entry in bootstrap/providers.php, which this test therefore does not cover.
*/

test('a module can add its permissions to the catalogue without editing config', function () {
    // Gap: permissions are listed in config/access-control.php, so a new module must edit it.
})->skip('GAP: permission catalogue is the hard-coded config/access-control.php');

test('a module can bring its own sidebar group without editing app/', function () {
    // Gap: Destinations::Groups, GroupLabels and GroupIcons are constants in app/Support/Desk/Destinations.php.
})->skip('GAP: sidebar groups are constants in App\Support\Desk\Destinations');

test('a module can register its Blade components and a morph-mapped model without editing app/', function () {
    // Gap: AppServiceProvider lists module component folders and the morph map by name.
})->skip('GAP: component folders and morph map live in App\Providers\AppServiceProvider');
