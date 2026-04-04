<?php

namespace Database\Seeders;

use App\Models\Airline;
use App\Models\Airport;
use App\Models\Flight;
use Illuminate\Database\Seeder;

class FlightSeeder extends Seeder
{
    public function run(): void
    {
        $ac = Airline::where('iata_code', 'AC')->first();
        $wj = Airline::where('iata_code', 'WJ')->first();

        $yul = Airport::where('iata_code', 'YUL')->first();
        $yvr = Airport::where('iata_code', 'YVR')->first();
        $yyz = Airport::where('iata_code', 'YYZ')->first();
        $yyc = Airport::where('iata_code', 'YYC')->first();

        $flights = [
            // Sample data from assignment spec
            [
                'airline_id' => $ac->id,
                'flight_number' => '301',
                'airport_departure_id' => $yul->id,
                'airport_arrival_id' => $yvr->id,
                'departure_time' => '07:35:00',
                'arrival_time' => '10:05:00',
                'price' => 273.23,
            ],
            [
                'airline_id' => $ac->id,
                'flight_number' => '302',
                'airport_departure_id' => $yvr->id,
                'airport_arrival_id' => $yul->id,
                'departure_time' => '11:30:00',
                'arrival_time' => '19:11:00',
                'price' => 220.63,
            ],
            // Additional flights for richer testing
            [
                'airline_id' => $ac->id,
                'flight_number' => '410',
                'airport_departure_id' => $yul->id,
                'airport_arrival_id' => $yyz->id,
                'departure_time' => '06:00:00',
                'arrival_time' => '07:20:00',
                'price' => 149.99,
            ],
            [
                'airline_id' => $ac->id,
                'flight_number' => '411',
                'airport_departure_id' => $yyz->id,
                'airport_arrival_id' => $yul->id,
                'departure_time' => '18:00:00',
                'arrival_time' => '19:20:00',
                'price' => 139.99,
            ],
            [
                'airline_id' => $wj->id,
                'flight_number' => '705',
                'airport_departure_id' => $yyc->id,
                'airport_arrival_id' => $yvr->id,
                'departure_time' => '09:15:00',
                'arrival_time' => '10:30:00',
                'price' => 189.50,
            ],
            [
                'airline_id' => $wj->id,
                'flight_number' => '706',
                'airport_departure_id' => $yvr->id,
                'airport_arrival_id' => $yyc->id,
                'departure_time' => '14:00:00',
                'arrival_time' => '15:15:00',
                'price' => 179.50,
            ],
        ];

        foreach ($flights as $flight) {
            Flight::firstOrCreate(
                [
                    'airline_id' => $flight['airline_id'],
                    'flight_number' => $flight['flight_number'],
                ],
                $flight
            );
        }
    }
}
