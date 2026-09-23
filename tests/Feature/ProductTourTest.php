<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ProductTour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ProductTourTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_configured_step_has_copy_in_both_languages(): void
    {
        foreach (config('tours') as $id => $tour) {
            foreach (array_keys($tour['steps']) as $key) {
                foreach (['en', 'ar'] as $locale) {
                    foreach (['title', 'body'] as $field) {
                        $this->assertTrue(
                            Lang::hasForLocale("tour.{$id}.{$key}.{$field}", $locale),
                            "Missing tour.{$id}.{$key}.{$field} for [{$locale}]",
                        );
                    }
                }
            }
        }
    }

    public function test_every_configured_route_exists(): void
    {
        foreach (config('tours') as $tour) {
            foreach ($tour['routes'] as $route) {
                $this->assertTrue(Route::has($route), "Tour route [{$route}] is not defined");
            }
        }
    }

    public function test_it_resolves_the_steps_for_the_current_route(): void
    {
        $request = Request::create('/');
        $request->setRouteResolver(fn () => Route::getRoutes()->getByName('leaves.index'));

        $tour = ProductTour::forRequest($request);

        $this->assertSame('leaves', $tour['id']);
        $this->assertSame('leaves.balances', $tour['steps'][0]['target']);
        $this->assertSame(__('tour.leaves.balances.title'), $tour['steps'][0]['title']);
    }

    public function test_only_the_dashboard_tour_opens_by_itself(): void
    {
        $resolve = function (string $route) {
            $request = Request::create('/');
            $request->setRouteResolver(fn () => Route::getRoutes()->getByName($route));

            return ProductTour::forRequest($request);
        };

        $this->assertTrue($resolve('dashboard')['autostart']);
        $this->assertFalse($resolve('leaves.index')['autostart']);
    }

    public function test_pages_without_a_tour_resolve_to_null(): void
    {
        $request = Request::create('/');
        $request->setRouteResolver(fn () => Route::getRoutes()->getByName('settings.password'));

        $this->assertNull(ProductTour::forRequest($request));
    }

    public function test_the_dashboard_renders_the_tour_and_its_targets(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('productTour(', false)
            ->assertSee(__('tour.dashboard.welcome.title'), false)
            ->assertSee('data-tour="tour.button"', false)
            ->assertSee('data-tour="dashboard.notes"', false)
            ->assertSee('data-tour="nav"', false)
            ->assertSee('data-tour="nav.dashboard"', false);
    }
}
