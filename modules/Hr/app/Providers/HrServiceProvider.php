<?php

namespace Modules\Hr\Providers;

use App\Models\Center;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Translation\FileLoader;
use Livewire\Livewire;
use Modules\Hr\Models\Badge;
use Modules\Hr\Models\Contract;
use Modules\Hr\Models\DefaultDeliverable;
use Modules\Hr\Models\Employee;
use Modules\Hr\Models\LeaveRequest;
use Modules\Hr\Models\Position;
use Modules\Hr\Observers\ContractObserver;
use Modules\Hr\Observers\EmployeeObserver;
use Modules\Hr\Policies\BadgePolicy;
use Modules\Hr\Policies\CenterPolicy;
use Modules\Hr\Policies\ContractPolicy;
use Modules\Hr\Policies\DefaultDeliverablePolicy;
use Modules\Hr\Policies\DigitalIdPolicy;
use Modules\Hr\Policies\EmployeePolicy;
use Modules\Hr\Policies\LeaveRequestPolicy;
use Modules\Hr\Policies\PositionPolicy;
use Modules\Hr\Services\SalaryCipher;
use ReflectionClass;

/**
 * Wires the HR module (modules/Hr) into the hub.
 *
 * HR's views, translations and Livewire components keep their original,
 * unprefixed names (`livewire.contract.*`, `__('leaves.*')`,
 * `<livewire:contract.contracts-table>`): the module's folders are added as
 * extra lookup locations rather than a namespace, so moving HR out of app/
 * changed no name the rest of the hub refers to. The `hr::` view namespace is
 * registered too, for new code that wants to say whose view it renders.
 */
class HrServiceProvider extends ServiceProvider
{
    /**
     * HR notification classes that lived in App\Notifications before the move
     * to modules/Hr (frozen: these are the names old queued jobs carry).
     *
     * @var list<string>
     */
    public const LEGACY_NOTIFICATIONS = [
        'ActionLeaveBalance', 'ActionLeaveRequest', 'CenterClosed', 'ChecklistAssigned',
        'ContractsAutoRenewedSummary', 'ContractsExpiringAutoRenewWarning', 'DigitalIdReportedLost',
        'FolderShared', 'HRSawLeaveRequest', 'LeaveDecisionReminder', 'LeaveRequestObjected',
        'NewCenterCreated', 'NewEmployeeWelcome', 'NewLeaveRequest', 'ProfileChangeDecided',
    ];

    public function register(): void
    {
        // Singleton so the Encrypter - and the "this host cannot read the
        // figures" warning that goes with it - is built once per request
        // rather than once per contract row rendered.
        $this->app->singleton(SalaryCipher::class);

        // Jobs queued by hc-hr before the move serialized its notifications as
        // App\Notifications\X. Resolve those names to the moved classes, so a
        // job still in the queue at deploy is sent instead of failing.
        spl_autoload_register(static function (string $class): void {
            $legacy = 'App\\Notifications\\';
            $name = substr($class, strlen($legacy));

            if (str_starts_with($class, $legacy) && in_array($name, self::LEGACY_NOTIFICATIONS, true)) {
                class_alias('Modules\\Hr\\Notifications\\'.$name, $class);
            }
        });
    }

    public function boot(): void
    {
        $module = dirname(__DIR__, 2);

        View::addLocation($module.'/resources/views');
        $this->loadViewsFrom($module.'/resources/views', 'hr');
        $this->callAfterResolving('translation.loader', function (FileLoader $loader) use ($module): void {
            $loader->addPath($module.'/lang');
        });
        $this->loadMigrationsFrom($module.'/database/migrations');
        Livewire::addLocation(classNamespace: 'Modules\\Hr\\Livewire');

        // Laravel discovers app/Console/Commands only; a module registers its own.
        $this->commands($this->moduleCommands($module.'/app/Console/Commands'));

        Gate::policy(Badge::class, BadgePolicy::class);
        Gate::policy(Center::class, CenterPolicy::class);
        Gate::policy(Contract::class, ContractPolicy::class);
        Gate::policy(DefaultDeliverable::class, DefaultDeliverablePolicy::class);
        Gate::policy(Employee::class, EmployeePolicy::class);
        Gate::policy(LeaveRequest::class, LeaveRequestPolicy::class);
        Gate::policy(Position::class, PositionPolicy::class);

        // Public digital-ID card management (access-policy.md §9 A2D-Q5).
        Gate::define('digital-id.view', [DigitalIdPolicy::class, 'view']);
        Gate::define('digital-id.manage', [DigitalIdPolicy::class, 'manage']);
        Gate::define('digital-id.report-lost', [DigitalIdPolicy::class, 'reportLost']);

        Contract::observe(ContractObserver::class);
        Employee::observe(EmployeeObserver::class);
    }

    /**
     * Every concrete command class in the module's Console/Commands folder.
     *
     * @return list<class-string<Command>>
     */
    private function moduleCommands(string $path): array
    {
        $commands = [];

        foreach (glob($path.'/*.php') ?: [] as $file) {
            $class = 'Modules\\Hr\\Console\\Commands\\'.basename($file, '.php');

            if (is_subclass_of($class, Command::class) && ! (new ReflectionClass($class))->isAbstract()) {
                $commands[] = $class;
            }
        }

        return $commands;
    }
}
