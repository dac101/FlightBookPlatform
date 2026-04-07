<?php

namespace Tests\Feature;

use App\Jobs\ProcessAviationStackFlightsJob;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class                ReprocessAviationStackCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake();
    }

    public function test_reprocesses_all_airports(): void
    {
        Queue::fake();

        Storage::put('aviation_stack/JFK/2026-04-05_10-00-00.json', '{}');
        Storage::put('aviation_stack/JFK/2026-04-06_10-00-00.json', '{}');
        Storage::put('aviation_stack/LAX/2026-04-06_10-00-00.json', '{}');

        $this->artisan('flights:reprocess')
            ->assertSuccessful()
            ->expectsOutputToContain('3 job(s) queued');

        Queue::assertPushed(ProcessAviationStackFlightsJob::class, 3);
    }

    public function test_reprocesses_single_airport_with_filter(): void
    {
        Queue::fake();

        Storage::put('aviation_stack/JFK/2026-04-06_10-00-00.json', '{}');
        Storage::put('aviation_stack/LAX/2026-04-06_10-00-00.json', '{}');

        $this->artisan('flights:reprocess --airport=JFK')
            ->assertSuccessful();

        Queue::assertPushed(ProcessAviationStackFlightsJob::class, 1);
        Queue::assertPushed(function (ProcessAviationStackFlightsJob $job) {
            return $job->iataCode === 'JFK';
        });
    }

    public function test_reprocesses_only_latest_file_per_airport(): void
    {
        Queue::fake();

        Storage::put('aviation_stack/JFK/2026-04-05_10-00-00.json', '{}');
        Storage::put('aviation_stack/JFK/2026-04-06_10-00-00.json', '{}');

        $this->artisan('flights:reprocess --latest')
            ->assertSuccessful();

        Queue::assertPushed(ProcessAviationStackFlightsJob::class, 1);
        Queue::assertPushed(function (ProcessAviationStackFlightsJob $job) {
            return str_contains($job->storagePath, '2026-04-06');
        });
    }

    public function test_reprocesses_single_file_by_path(): void
    {
        Queue::fake();

        Storage::put('aviation_stack/MIA/2026-04-06_08-00-00.json', '{}');

        $this->artisan('flights:reprocess --file=aviation_stack/MIA/2026-04-06_08-00-00.json')
            ->assertSuccessful();

        Queue::assertPushed(ProcessAviationStackFlightsJob::class, 1);
        Queue::assertPushed(function (ProcessAviationStackFlightsJob $job) {
            return $job->iataCode === 'MIA'
                && $job->storagePath === 'aviation_stack/MIA/2026-04-06_08-00-00.json';
        });
    }

    public function test_returns_failure_when_single_file_not_found(): void
    {
        Queue::fake();

        $this->artisan('flights:reprocess --file=aviation_stack/MIA/missing.json')
            ->assertFailed();

        Queue::assertNothingPushed();
    }

    public function test_returns_failure_when_airport_not_found(): void
    {
        Queue::fake();

        $this->artisan('flights:reprocess --airport=XXX')
            ->assertFailed();

        Queue::assertNothingPushed();
    }
}
