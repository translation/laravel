<?php

namespace Armandsar\LaravelTranslationio\Console\Commands;

use Illuminate\Console\Command;
use Armandsar\LaravelTranslationio\Service\SourceEditSync as SourceEditSyncService;
use Armandsar\LaravelTranslationio\Service\Sync as SyncService;

class SyncAndShowPurgeable extends Command
{
    protected $signature = 'translation:sync_and_show_purgeable';

    protected $description = 'Sync translations and found out the unused keys/string from Translation.io, using the current branch as reference.';

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
        $this->sourceEditSyncService->call();
        $this->syncService->call($this, [
            'purge' => false,
            'show_purgeable' => true
        ]);
    }
}
