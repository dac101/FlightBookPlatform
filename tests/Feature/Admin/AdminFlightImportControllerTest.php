<?php

namespace Tests\Feature\Admin;

use App\Jobs\ProcessAviationStackFlightsJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsFlightData;
use Tests\TestCase;

class AdminFlightImportControllerTest extends TestCase
{
    use BuildsFlightData;
    use RefreshDatabase;

    public function test_admin_can_upload_a_flight_import_file(): void
    {
        Queue::fake();
        Storage::fake();

        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)
            ->postJson(route('admin.api.flight-imports.upload'), [
                'iata_code' => 'YUL',
                'file' => UploadedFile::fake()->createWithContent('arrivals.json', '{"data":[]}'),
            ]);

        $response->assertOk()
            ->assertJsonPath('message', 'File saved and job dispatched to Horizon for YUL.');

        $path = $response->json('path');

        Storage::assertExists($path);
        Queue::assertPushed(ProcessAviationStackFlightsJob::class);
    }
}
