<?php

namespace App\Jobs;

use App\Services\FlightImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessAviationStackFlightsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public readonly string $storagePath,
        public readonly string $iataCode,
    ) {}

    /**
     * Laravel injects the service via the container when the job is handled.
     */
    public function handle(FlightImportService $flightImportService): void
    {
        Log::channel('flights')->info('ProcessAviationStackFlightsJob: started', [
            'airport' => $this->iataCode,
            'path' => $this->storagePath,
        ]);

        $result = $flightImportService->processStorageFile($this->storagePath, $this->iataCode);

        Log::channel('flights')->info('ProcessAviationStackFlightsJob: completed', [
            'airport' => $this->iataCode,
            'processed' => $result['processed'],
            'skipped' => $result['skipped'],
            'errors' => $result['errors'],
        ]);
    }
}
