<?php


namespace Armandsar\LaravelTranslationio\Console\Commands;

use Illuminate\Console\Command;
use Armandsar\LaravelTranslationio\Service\Init as InitService;

class Init extends Command
{
    protected $signature = 'translations:init';

    protected $description = 'Initialize translation.io';

    /**
     * @var InitService
     */
    private $service;

    public function __construct(InitService $service)
    {
        $this->service = $service;
        parent::__construct();
    }

    public function handle()
    {
        $this->info('Init started');
        $this->service->call();
        $this->info('Init finished');
    }
}
