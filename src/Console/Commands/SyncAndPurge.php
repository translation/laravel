<?php

namespace Armandsar\LaravelTranslationio\Console\Commands;

use Illuminate\Console\Command;
use Armandsar\LaravelTranslationio\Service\SourceEditSync as SourceEditSyncService;
use Armandsar\LaravelTranslationio\Service\Sync as SyncService;

class SyncAndPurge extends Command
{
    protected $signature = 'translation:sync_and_purge';

    protected $description = 'Sync translations and remove unused keys from Translation.io, using the current branch as reference.';

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
        $this->info('SyncAndPurge started');
        $this->sourceEditSyncService->call();
        $this->syncService->call($this, [
            'purge' => true,
            'show_purgeable' => false
        ]);
    }
}
