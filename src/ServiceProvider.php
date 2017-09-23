<?php namespace Armandsar\LaravelTranslationio;

use Armandsar\LaravelTranslationio\Console\Commands\Init;
use Armandsar\LaravelTranslationio\Console\Commands\Sync;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ServiceProvider extends LaravelServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->config();
        $this->configureCommands();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
    }

    private function config()
    {
        $configPath = __DIR__ . '/../config/translationio.php';

        $this->publishes([$configPath => config_path('translationio.php')]);

        $this->mergeConfigFrom($configPath, 'translationio');
    }

    private function configureCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Init::class, Sync::class
            ]);
        }
    }
}
