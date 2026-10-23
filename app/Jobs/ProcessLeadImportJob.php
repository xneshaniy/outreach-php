<?php

namespace App\Jobs;

use App\Models\LeadImport;
use App\Services\ImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessLeadImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 300;

    public function __construct(
        public int $importId
    ) {}

    public function handle(ImportService $importService): void
    {
        $import = LeadImport::find($this->importId);
        if (!$import) {
            return;
        }

        $importService->processImport($import);
    }
}