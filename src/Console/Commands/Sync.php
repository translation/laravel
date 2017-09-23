<?php


namespace Armandsar\LaravelTranslationio\Console\Commands;

use Illuminate\Console\Command;
use Armandsar\LaravelTranslationio\Service\Sync as SyncService;

class Sync extends Command
{
    protected $signature = 'translations:sync';

    protected $description = 'Sync translations with translation.io';

    /**
     * @var SyncService
     */
    private $service;

    public function __construct(SyncService $service)
    {
        $this->service = $service;
        parent::__construct();
    }

    public function handle()
    {
        $this->info('Sync started');
        $this->service->call();
        $this->info('Sync finished');
    }
}
