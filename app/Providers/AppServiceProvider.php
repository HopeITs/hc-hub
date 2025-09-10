<?php

namespace App\Providers;

use App\Models\Contract;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{

    public function register(): void
    {
        //
    }

    public function boot(): void
    {

        Builder::macro('search', function ($fields, $string) {
            if (!$string) {
                return $this;
            }

            $fields = Arr::wrap($fields);

            return $this->where(function ($query) use ($fields, $string) {
                foreach ($fields as $field) {
                    $query->orWhere($field, 'like', '%' . $string . '%');
                }

            });


        });
        Builder::macro('findBySlug', function ($fields, $string) {
            if (!$string) {
                return $this;
            }

            $fields = Arr::wrap($fields);

            return $this->where(function ($query) use ($fields, $string) {
                foreach ($fields as $field) {
                    $query->orWhere($field, 'like', '%' . $string . '%');
                }

            });

        });
    }
}
