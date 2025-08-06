<?php

namespace App\Providers;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Builder::macro('search', function ($fields, $string) {
            if (!$string) {
                return $this;
            }

            $fields = Arr::wrap($fields); // ensure $fields is always an array

            return $this->where(function ($query) use ($fields, $string) {
                foreach ($fields as $field) {
                    $query->orWhere($field, 'like', '%' . $string . '%');
                }

            });


        });
    }
}
