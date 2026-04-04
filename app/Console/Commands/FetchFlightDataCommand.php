<?php

namespace App\Console\Commands;

use App\Services\AviationStackService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('flights:fetch')]
#[Description('Fetch live arrival data from AviationStack for key airports and dispatch to Horizon for processing')]
class FetchFlightDataCommand extends Command
{
    /**
     * Airports to fetch — 8 total, consuming 8 of your 100 monthly API requests per run.
     * At daily scheduling that is 8 requests/day — adjust frequency to stay within quota.
     *
     * @var array<string>
     */
    private const AIRPORTS = [
        //        'MBJ', // Sangster International — Montego Bay, Jamaica
        //        'JFK', // John F. Kennedy — New York
        'YUL', // Pierre Elliott Trudeau — Montreal
        /*        'YYZ', // Pearson — Toronto
        'YVR', // Vancouver International
        'LAX', // Los Angeles International
        'MIA', // Miami International
        'ORD', // O'Hare — Chicago*/
    ];

    public function __construct(private readonly AviationStackService $aviationStack)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->components->info('Starting AviationStack flight fetch for '.count(self::AIRPORTS).' airports.');

        foreach (self::AIRPORTS as $iataCode) {
            $this->components->task("Fetching arrivals for {$iataCode}", function () use ($iataCode) {
                $data = $this->aviationStack->fetchArrivals($iataCode);

                if ($data === null) {
                    $this->components->warn("  Skipped {$iataCode} — fetch failed (see logs).");

                    return false;
                }

                $storagePath = $this->aviationStack->saveToJson($iataCode, $data);

                if ($storagePath === null) {
                    $this->components->warn("  Skipped {$iataCode} — could not save JSON (see logs).");

                    return false;
                }

                $this->line('  → '.count($data['data'] ?? []).' flights saved, job dispatched to Horizon.');

                return true;
            });
        }

        $this->newLine();
        $this->components->success('All airports fetched. Jobs are queued in Horizon.');

        return self::SUCCESS;
    }
}
