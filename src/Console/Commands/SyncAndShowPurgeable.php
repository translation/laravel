<?php

namespace Tio\Laravel\Console\Commands;

use Illuminate\Console\Command;
use Tio\Laravel\Service\SourceEditSync as SourceEditSyncService;
use Tio\Laravel\Service\Sync as SyncService;

class SyncAndShowPurgeable extends Command
{
    protected $signature = 'translation:sync_and_show_purgeable';

    protected $description = 'Sync translations and find out the unused keys/string from Translation.io, using the current branch as reference.';

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
        $this->info('SyncAndShowPurgeable started');
        $this->sourceEditSyncService->call($this);
        $this->syncService->call($this, [
            'purge' => false,
            'show_purgeable' => true
        ]);
    }
}
