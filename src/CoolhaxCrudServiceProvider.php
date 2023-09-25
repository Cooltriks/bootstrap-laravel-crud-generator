<?php

namespace Coolhax\CoolhaxCrudGenerator;

use Coolhax\CoolhaxCrudGenerator\Commands\CoolhaxCrudGenerator;
use Illuminate\Support\ServiceProvider;

/**
 * Class CrudServiceProvider.
 */
class CoolhaxCrudServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CoolhaxCrudGenerator::class,
            ]);
        }

        $this->publishes([
            __DIR__.'/config/crud-bootstrap.php' => config_path('crud-bootstrap.php'),
        ], 'crud');
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
