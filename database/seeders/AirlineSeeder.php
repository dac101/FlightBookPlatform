<?php

namespace Database\Seeders;

use App\Models\Airline;
use Illuminate\Database\Seeder;

class AirlineSeeder extends Seeder
{
    public function run(): void
    {
        $airlines = [
            ['iata_code' => 'AC', 'name' => 'Air Canada'],
            ['iata_code' => 'WJ', 'name' => 'WestJet'],
            ['iata_code' => 'PD', 'name' => 'Porter Airlines'],
        ];

        foreach ($airlines as $airline) {
            Airline::firstOrCreate(['iata_code' => $airline['iata_code']], $airline);
        }
    }
}
