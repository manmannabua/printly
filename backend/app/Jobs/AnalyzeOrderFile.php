<?php

namespace App\Jobs;

use App\Models\OrderFile;
use App\Services\FileAnalysisService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Runs file analysis off the request cycle (planning §5.1 step 2).
 */
class AnalyzeOrderFile implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly string $orderFileId) {}

    public function handle(FileAnalysisService $analysis): void
    {
        $file = OrderFile::find($this->orderFileId);

        if ($file) {
            $analysis->analyze($file);
        }
    }
}
