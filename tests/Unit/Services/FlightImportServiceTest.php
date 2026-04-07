<?php

namespace Tests\Unit\Services;

use App\Models\Airline;
use App\Models\Airport;
use App\Repositories\Contracts\AirportCsvRepositoryInterface;
use App\Repositories\Contracts\FlightImportRepositoryInterface;
use App\Services\FlightImportService;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\TestCase;

class FlightImportServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_process_storage_file_returns_zeroes_when_file_is_missing(): void
    {
        Storage::fake(config('filesystems.default', 'local'));

        $repository = Mockery::mock(FlightImportRepositoryInterface::class);
        $airportCsvRepository = Mockery::mock(AirportCsvRepositoryInterface::class);
        $service = new FlightImportService($repository, $airportCsvRepository);

        $result = $service->processStorageFile('aviation_stack/YUL/missing.json', 'YUL');

        $this->assertSame([
            'processed' => 0,
            'skipped' => 0,
            'errors' => 0,
        ], $result);
    }

    public function test_process_storage_file_processes_valid_record(): void
    {
        Storage::fake(config('filesystems.default', 'local'));

        $path = 'aviation_stack/YUL/sample.json';
        Storage::put($path, json_encode([
            'data' => [[
                'airline' => ['iata' => 'AC', 'name' => 'Air Canada'],
                'flight' => ['number' => '123'],
                'departure' => ['iata' => 'YUL', 'scheduled' => '2026-04-08T10:00:00+00:00'],
                'arrival' => ['iata' => 'YYZ', 'scheduled' => '2026-04-08T11:30:00+00:00'],
            ]],
        ]));

        $departureAirport = new Airport(['name' => 'Montreal Trudeau', 'iata_code' => 'YUL']);
        $departureAirport->id = 1;
        $arrivalAirport = new Airport(['name' => 'Toronto Pearson', 'iata_code' => 'YYZ']);
        $arrivalAirport->id = 2;
        $airline = new Airline(['name' => 'Air Canada', 'iata_code' => 'AC']);
        $airline->id = 3;

        $repository = Mockery::mock(FlightImportRepositoryInterface::class);
        $repository->shouldReceive('findAirportByIata')->once()->with('YUL')->andReturn($departureAirport);
        $repository->shouldReceive('findAirportByIata')->once()->with('YYZ')->andReturn($arrivalAirport);
        $repository->shouldReceive('findOrCreateAirline')->once()->with('AC', 'Air Canada')->andReturn($airline);
        $repository->shouldReceive('flightExists')->once()->with(3, '123')->andReturnFalse();
        $repository->shouldReceive('createFlight')->once()->with(Mockery::on(function (array $data): bool {
            return $data['airline_id'] === 3
                && $data['airport_departure_id'] === 1
                && $data['airport_arrival_id'] === 2
                && $data['flight_number'] === '123';
        }));

        $airportCsvRepository = Mockery::mock(AirportCsvRepositoryInterface::class);
        $service = new FlightImportService($repository, $airportCsvRepository);

        $result = $service->processStorageFile($path, 'YUL');

        $this->assertSame(1, $result['processed']);
        $this->assertSame(0, $result['skipped']);
        $this->assertSame(0, $result['errors']);
    }
}
