<?php

namespace Armandsar\LaravelTranslationio;

use Armandsar\LaravelTranslationio\Console\Commands\Init;
use Armandsar\LaravelTranslationio\Console\Commands\Sync;
use Armandsar\LaravelTranslationio\Console\Commands\SyncAndPurge;
use Armandsar\LaravelTranslationio\Console\Commands\SyncAndShowPurgeable;
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

        $config = config('translationio');

        $translationIO = new TranslationIO($config);

        $translationIO->setLocale($config['source_locale']);
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
                Init::class,
                Sync::class,
                SyncAndPurge::class,
                SyncAndShowPurgeable::class
            ]);
        }
    }
}
