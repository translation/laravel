<?php

namespace Tio\Laravel;

use Tio\Laravel\Console\Commands\Init;
use Tio\Laravel\Console\Commands\Sync;
use Tio\Laravel\Console\Commands\SyncAndPurge;
use Tio\Laravel\Console\Commands\SyncAndShowPurgeable;
use Tio\Laravel\Middleware\SetLocaleMiddleware;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use Illuminate\Foundation\AliasLoader;

class ServiceProvider extends LaravelServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Register the service provider (for bindings).
     *
     * @return void
     */
    public function register()
    {
        $router = $this->app['router'];

        if (method_exists($router, 'aliasMiddleware')) {
          $router->aliasMiddleware('set.locale' , SetLocaleMiddleware::class);
        }
        else {
          $router->middleware('set.locale' , SetLocaleMiddleware::class);
        }
    }

    /**
     * Bootstrap the application events (executed after all services are booted).
     *
     * @return void
     */
    public function boot()
    {
        $this->config();
        $this->configureCommands();

        $config = config('translation');

        $translationIO = new TranslationIO($config);

        $translationIO->setLocale(config('app.locale'));
    }

    private function config()
    {
        $configPath = __DIR__ . '/../config/translation.php';

        $this->publishes([$configPath => config_path('translation.php')]);

        $this->mergeConfigFrom($configPath, 'translation');
    }

    private function configureCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Init::class,
                Sync::class,
                SyncAndPurge::class,
                SyncAndShowPurgeable::class
            ]);
        }
    }
}
