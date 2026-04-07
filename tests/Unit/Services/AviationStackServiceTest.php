<?php

namespace Tests\Unit\Services;

use App\Jobs\ProcessAviationStackFlightsJob;
use App\Services\AviationStackService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AviationStackServiceTest extends TestCase
{
    public function test_fetch_arrivals_returns_response_data_on_success(): void
    {
        Http::fake([
            'https://api.aviationstack.com/v1/flights*' => Http::response([
                'data' => [
                    ['flight' => ['number' => 'AC123']],
                ],
            ], 200),
        ]);

        $service = new AviationStackService('secret');
        $result = $service->fetchArrivals('yul');

        $this->assertIsArray($result);
        $this->assertCount(1, $result['data']);
    }

    public function test_save_to_json_persists_file_and_dispatches_job(): void
    {
        Storage::fake(config('filesystems.default', 'local'));
        Queue::fake();
        Carbon::setTestNow(Carbon::parse('2026-04-07 12:30:45'));

        $service = new AviationStackService('secret');
        $path = $service->saveToJson('yul', ['data' => [['flight' => ['number' => 'AC123']]]]);

        $this->assertSame('aviation_stack/YUL/2026-04-07_12-30-45.json', $path);
        Storage::assertExists($path);
        Queue::assertPushed(ProcessAviationStackFlightsJob::class, function (ProcessAviationStackFlightsJob $job): bool {
            return $job->storagePath === 'aviation_stack/YUL/2026-04-07_12-30-45.json'
                && $job->iataCode === 'yul';
        });

        Carbon::setTestNow();
    }
}
