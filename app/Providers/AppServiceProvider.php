<?php

namespace App\Providers;

use App\Http\Middleware\ActiveLivewirePermissionMiddleware;
use App\Models\Badge;
use App\Models\Contract;
use App\Models\LeaveRequest;
use App\Observers\ContractObserver;
use App\Policies\BadgePolicy;
use App\Policies\LeaveRequestPolicy;
use App\Services\ActivityLogger;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ActivityLogger::class, function ($app) {
            return new ActivityLogger;
        });
    }

    public function boot(): void
    {
        Gate::policy(Badge::class, BadgePolicy::class);
        Gate::policy(LeaveRequest::class, LeaveRequestPolicy::class);

        Livewire::setUpdateRoute(function ($handle) {
            return Route::post('/livewire/update', $handle)
                ->middleware(['web', ActiveLivewirePermissionMiddleware::class])
                ->prefix(LaravelLocalization::setLocale());
        });

        Blade::directive('glyph', function ($expr) {
            return '<?php echo app(\App\Support\Arabic::class)->glyphs('.$expr.'); ?>';
        });

        Builder::macro('whereLikeAny', function ($fields, string $search) {
            $fields = Arr::wrap($fields);
            if ($search === '' || $search === null) {
                return $this;
            }

            return $this->where(function ($q) use ($fields, $search) {
                foreach ($fields as $field) {
                    $q->orWhere($field, 'like', "%{$search}%");
                }
            });
        });

        Builder::macro('search', function ($fields, $string) {
            if (! $string) {
                return $this;
            }

            $fields = Arr::wrap($fields);

            return $this->where(function ($query) use ($fields, $string) {
                foreach ($fields as $field) {
                    $query->orWhere($field, 'like', '%'.$string.'%');
                }

            });

        });
        Builder::macro('findBySlug', function ($fields, $string) {
            if (! $string) {
                return $this;
            }

            $fields = Arr::wrap($fields);

            return $this->where(function ($query) use ($fields, $string) {
                foreach ($fields as $field) {
                    $query->orWhere($field, 'like', '%'.$string.'%');
                }

            });

        });

        Blade::if('activerole', function (...$roles) {
            $user = auth()->user();

            return $user?->activeRoleIs(...$roles) ?? false;
        });

        Blade::if('notactiverole', function (...$roles) {
            return auth()->check() && ! auth()->user()->activeRoleIs(...$roles);
        });

        Blade::if('activepermission', function (string $permission) {
            return auth()->user()?->hasActivePermission($permission) ?? false;
        });

        Contract::observe(ContractObserver::class);
    }
}
