<?php

namespace App\Console\Commands;

use App\Services\TempFileService;
use Illuminate\Console\Command;

class CleanupTempFiles extends Command
{
    protected $signature   = 'files:cleanup-temp';
    protected $description = 'Remove temporary uploaded files older than 24 hours';

    public function handle(TempFileService $service): int
    {
        $deleted = $service->cleanupOldFiles();
        $this->info("Cleaned up {$deleted} stale temporary file(s).");
        return self::SUCCESS;
    }
}
