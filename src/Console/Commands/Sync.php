<?php


namespace Armandsar\LaravelTranslationio\Console\Commands;

use Illuminate\Console\Command;
use Armandsar\LaravelTranslationio\Service\SourceEditSync as SourceEditSyncService;
use Armandsar\LaravelTranslationio\Service\Sync as SyncService;

class Sync extends Command
{
    protected $signature = 'translations:sync';

    protected $description = 'Sync translations with translation.io';

    /**
     * @var SyncService
     */
    private $syncService;
    /**
     * @var SourceEditSyncService
     */
    private $sourceEditSyncService;

    public function __construct(
        SourceEditSyncService $sourceEditSyncService,
        SyncService $syncService
    )
    {
        $this->sourceEditSyncService = $sourceEditSyncService;
        $this->syncService = $syncService;
        parent::__construct();
    }

    public function handle()
    {
        $this->info('Sync started');
        $this->sourceEditSyncService->call();
        $this->syncService->call();
        $this->info('Sync finished');
    }
}
