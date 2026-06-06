<?php

namespace App\Jobs;

use App\Services\ReferenceDataSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncFbrReferenceDataJob implements ShouldQueue
{
    use Queueable;

    public function handle(ReferenceDataSyncService $service): void
    {
        $service->sync();
    }
}
