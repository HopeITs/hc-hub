<?php

namespace App\Providers;

use App\Models\Contract;
use App\Policies\ContractPolicy;
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

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::directive('glyph', function ($expr) {
        return '<?php echo app(\App\Support\Arabic::class)->glyphs(' . $expr . '); ?>';
        });

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
