<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('flights', function (Blueprint $table) {
            $table->id();
            $table->string('flight_number', 10);
            $table->foreignId('airline_id')->constrained()->cascadeOnDelete();
            $table->foreignId('airport_departure_id')->constrained('airports')->cascadeOnDelete();
            $table->foreignId('airport_arrival_id')->constrained('airports')->cascadeOnDelete();
            $table->time('departure_time');
            $table->time('arrival_time');
            $table->decimal('price', 10, 2);
            $table->timestamps();

            $table->unique(['airline_id', 'flight_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flights');
    }
};
