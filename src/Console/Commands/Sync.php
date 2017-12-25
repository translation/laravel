<?php


namespace Armandsar\LaravelTranslationio\Console\Commands;

use Illuminate\Console\Command;
use Armandsar\LaravelTranslationio\Service\Sync as SyncService;
use Armandsar\LaravelTranslationio\Service\SourceEditSync as SourceEditSyncService;

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
      SyncService $syncService,
      SourceEditSyncService $sourceEditSyncService
    )
    {
        $this->syncService = $syncService;
        $this->sourceEditSyncService = $sourceEditSyncService;
        parent::__construct();
    }

    public function handle()
    {
        $this->info('Sync started');
        $this->syncService->call();
        $this->sourceEditSyncService->call();
        $this->info('Sync finished');
    }
}
