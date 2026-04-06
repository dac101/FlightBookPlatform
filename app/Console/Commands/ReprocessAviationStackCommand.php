<?php

namespace App\Console\Commands;

use App\Jobs\ProcessAviationStackFlightsJob;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('flights:reprocess
    {--airport= : Only reprocess files for this IATA code (e.g. JFK)}
    {--file= : Reprocess a single file (relative storage path, e.g. aviation_stack/JFK/2026-04-06_06-00-03.json)}
    {--latest : Only reprocess the most recent file per airport}')]
#[Description('Re-dispatch stored aviation_stack JSON files to Horizon for reprocessing')]
class ReprocessAviationStackCommand extends Command
{
    public function handle(): int
    {
        if ($this->option('file')) {
            return $this->reprocessSingleFile($this->option('file'));
        }

        return $this->reprocessFolder();
    }

    private function reprocessSingleFile(string $relativePath): int
    {
        if (! Storage::exists($relativePath)) {
            $this->components->error("File not found in storage: {$relativePath}");

            return self::FAILURE;
        }

        $iataCode = $this->iataFromPath($relativePath);

        ProcessAviationStackFlightsJob::dispatch($relativePath, $iataCode)
            ->onQueue('flight-imports');

        $this->components->info("Dispatched job for: {$relativePath} (airport: {$iataCode})");

        return self::SUCCESS;
    }

    private function reprocessFolder(): int
    {
        $airportFilter = $this->option('airport')
            ? strtoupper($this->option('airport'))
            : null;

        $airports = collect(Storage::directories('aviation_stack'))
            ->map(fn (string $dir) => basename($dir))
            ->when($airportFilter, fn ($col) => $col->filter(fn ($code) => $code === $airportFilter))
            ->sort()
            ->values();

        if ($airports->isEmpty()) {
            $label = $airportFilter ? "airport {$airportFilter}" : 'aviation_stack folder';
            $this->components->warn("No airport directories found for {$label}.");

            return self::FAILURE;
        }

        $dispatched = 0;

        foreach ($airports as $iataCode) {
            $files = collect(Storage::files("aviation_stack/{$iataCode}"))
                ->filter(fn (string $f) => str_ends_with($f, '.json'))
                ->sort()
                ->values();

            if ($files->isEmpty()) {
                $this->components->warn("No JSON files found for {$iataCode}, skipping.");

                continue;
            }

            if ($this->option('latest')) {
                $files = $files->slice(-1)->values();
            }

            foreach ($files as $path) {
                ProcessAviationStackFlightsJob::dispatch($path, $iataCode)
                    ->onQueue('flight-imports');

                $dispatched++;
            }

            $label = $this->option('latest') ? '1 file (latest)' : "{$files->count()} file(s)";
            $this->components->task("{$iataCode}: dispatched {$label}");
        }

        $this->newLine();
        $this->components->success("Done — {$dispatched} job(s) queued on the flight-imports queue.");

        return self::SUCCESS;
    }

    private function iataFromPath(string $path): string
    {
        // aviation_stack/JFK/filename.json → JFK
        $parts = explode('/', $path);
        $index = array_search('aviation_stack', $parts);

        return isset($parts[$index + 1]) ? strtoupper($parts[$index + 1]) : 'UNKNOWN';
    }
}
