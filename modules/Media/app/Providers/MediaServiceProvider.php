<?php

namespace Modules\Media\Providers;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Translation\FileLoader;
use Livewire\Livewire;
use Modules\Media\Models\MediaRequest;
use Modules\Media\Policies\MediaRequestPolicy;
use Modules\Media\Support\MediaFolders;
use Modules\Media\Support\MediaTeam;
use ReflectionClass;
use Spatie\MediaLibrary\Support\PathGenerator\PathGeneratorFactory;

/**
 * Wires the Media requests module (modules/Media) into the hub.
 *
 * Same pattern as HrServiceProvider: the module's views, translations and
 * Livewire components keep their plain names (`livewire.media-requests.*`,
 * `__('media.*')`, `<livewire:media-requests.show>`), plus the `media::` view
 * namespace. Its settings are config('media-requests').
 */
class MediaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $module = dirname(__DIR__, 2);

        $this->mergeConfigFrom($module.'/config/media-requests.php', 'media-requests');

        // Who is on the team / manages a program: memoized per request.
        $this->app->scoped(MediaTeam::class);
    }

    public function boot(): void
    {
        $module = dirname(__DIR__, 2);

        View::addLocation($module.'/resources/views');
        $this->loadViewsFrom($module.'/resources/views', 'media');
        $this->callAfterResolving('translation.loader', function (FileLoader $loader) use ($module): void {
            $loader->addPath($module.'/lang');
        });
        $this->loadMigrationsFrom($module.'/database/migrations');
        Livewire::addLocation(classNamespace: 'Modules\\Media\\Livewire');

        $this->commands($this->moduleCommands($module.'/app/Console/Commands'));

        Gate::policy(MediaRequest::class, MediaRequestPolicy::class);

        // Request files go in program / governorate / request folders — for
        // media requests only, never as the library's global path generator.
        PathGeneratorFactory::setCustomPathGenerators(MediaRequest::class, MediaFolders::class);
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
            $class = 'Modules\\Media\\Console\\Commands\\'.basename($file, '.php');

            if (is_subclass_of($class, Command::class) && ! (new ReflectionClass($class))->isAbstract()) {
                $commands[] = $class;
            }
        }

        return $commands;
    }
}
