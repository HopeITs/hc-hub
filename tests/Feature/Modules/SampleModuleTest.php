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
use App\Support\Modules\Contracts\ScheduleSource;
use App\Support\Modules\Contracts\SearchSource;
use App\Support\Modules\Contracts\SubjectLinkSource;
use App\Support\Modules\Contracts\TimelineSource;
use App\Support\Modules\Contracts\WorkItemSource;
use App\Support\Modules\ModuleRegistry;
use App\Support\Timeline\EmployeeTimeline;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Schedule;
use Modules\Core\Models\User;
use Tests\Fixtures\Modules\Sample\SampleAgenda;
use Tests\Fixtures\Modules\Sample\SampleCalendarLayer;
use Tests\Fixtures\Modules\Sample\SampleDestinations;
use Tests\Fixtures\Modules\Sample\SampleEmployeeTab;
use Tests\Fixtures\Modules\Sample\SampleMetrics;
use Tests\Fixtures\Modules\Sample\SamplePanels;
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
        $read = $reader === 'subjectLinkSourceFor'
            ? [$this->registry->subjectLinkSourceFor(SampleSubjectLinks::Type)]
            : $this->registry->{$reader}();

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

test('the Desk agenda, calendar, work items and search read the module through the registry', function () {
    $from = CarbonImmutable::parse('2026-03-02');
    $to = $from->addDays(6);

    $agenda = collect($this->registry->deskAgendaSources())->first(fn ($source) => $source instanceof SampleAgenda);
    $layer = collect($this->registry->calendarLayerSources())->first(fn ($source) => $source instanceof SampleCalendarLayer);
    $work = collect($this->registry->workItemSources())->first(fn ($source) => $source instanceof SampleWorkItems);
    $search = collect($this->registry->searchSources())->first(fn ($source) => $source instanceof SampleSearch);

    expect($agenda->between($this->viewer, $from, $to)->pluck('title')->all())->toBe(['Sample agenda row'])
        ->and($layer->allows($this->viewer))->toBeTrue()
        ->and($layer->between($this->viewer, $from, $to))->toHaveKey('2026-03-02')
        ->and($work->allows($this->viewer))->toBeTrue()
        ->and($work->count($this->viewer))->toBe(1)
        ->and($work->items($this->viewer)[0]['title'])->toBe('Sample item')
        ->and($search->allows($this->viewer))->toBeTrue()
        ->and($search->search($this->viewer, 'thing', 5)[0]['title'])->toBe('Sample thing');
});

test('the employee profile gets the module tab and the timeline gets its entries', function () {
    $tab = collect($this->registry->employeeTabSources())->first(fn ($source) => $source instanceof SampleEmployeeTab);
    $entries = app(EmployeeTimeline::class)->entries($this->viewer, 1);

    expect($tab->visibleTo($this->viewer, 1))->toBeTrue()
        ->and($tab->component())->toBe('sample::employees.tab')
        ->and(collect($entries)->firstWhere('source', 'sample'))->toMatchArray(['title' => 'Sample entry', 'label' => 'Sample']);
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

test('the module schedule is read when the scheduler is built', function () {
    $schedule = new Schedule;

    foreach ($this->registry->scheduleSources() as $source) {
        $source->schedule($schedule);
    }

    expect(collect($schedule->events())->pluck('description')->all())->toContain(SampleSchedule::Name);
});

test('the module declares a permission and a prefixed route of its own', function () {
    expect($this->viewer->can(SampleServiceProvider::Ability))->toBeTrue()
        ->and(route('sample.index', absolute: false))->toBe('/sample');

    $this->get('/sample')->assertOk()->assertSee('sample module');
});

test('a module can add its permissions to the catalogue without editing config', function () {
    // Gap: permissions are listed in config/access-control.php, so a new module must edit it.
})->skip('GAP: permission catalogue is the hard-coded config/access-control.php');

test('a module can bring its own sidebar group without editing app/', function () {
    // Gap: Destinations::Groups, GroupLabels and GroupIcons are constants in app/Support/Desk/Destinations.php.
})->skip('GAP: sidebar groups are constants in App\Support\Desk\Destinations');

test('a module can register its Blade components and a morph-mapped model without editing app/', function () {
    // Gap: AppServiceProvider lists module component folders and the morph map by name.
})->skip('GAP: component folders and morph map live in App\Providers\AppServiceProvider');
