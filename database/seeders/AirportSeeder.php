<?php

namespace Database\Seeders;

use App\Models\Airport;
use Illuminate\Database\Seeder;

class AirportSeeder extends Seeder
{
    public function run(): void
    {
        $airports = [
            [
                'iata_code' => 'YUL',
                'city_code' => 'YMQ',
                'name' => 'Pierre Elliott Trudeau International',
                'city' => 'Montreal',
                'country_code' => 'CA',
                'region_code' => 'QC',
                'latitude' => 45.457714,
                'longitude' => -73.749908,
                'timezone' => 'America/Toronto',
            ],
            [
                'iata_code' => 'YVR',
                'city_code' => 'YVR',
                'name' => 'Vancouver International',
                'city' => 'Vancouver',
                'country_code' => 'CA',
                'region_code' => 'BC',
                'latitude' => 49.194698,
                'longitude' => -123.179192,
                'timezone' => 'America/Vancouver',
            ],
            [
                'iata_code' => 'YYZ',
                'city_code' => 'YTO',
                'name' => 'Toronto Pearson International',
                'city' => 'Toronto',
                'country_code' => 'CA',
                'region_code' => 'ON',
                'latitude' => 43.677223,
                'longitude' => -79.630556,
                'timezone' => 'America/Toronto',
            ],
            [
                'iata_code' => 'YYC',
                'city_code' => 'YYC',
                'name' => 'Calgary International',
                'city' => 'Calgary',
                'country_code' => 'CA',
                'region_code' => 'AB',
                'latitude' => 51.113888,
                'longitude' => -114.020278,
                'timezone' => 'America/Edmonton',
            ],
        ];

        foreach ($airports as $airport) {
            Airport::firstOrCreate(['iata_code' => $airport['iata_code']], $airport);
        }
    }
}
