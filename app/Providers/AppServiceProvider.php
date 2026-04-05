<?php

namespace App\Providers;

use App\Repositories\AirlineRepository;
use App\Repositories\AirportCsvRepository;
use App\Repositories\AirportRepository;
use App\Repositories\Contracts\AirlineRepositoryInterface;
use App\Repositories\Contracts\AirportCsvRepositoryInterface;
use App\Repositories\Contracts\AirportRepositoryInterface;
use App\Repositories\Contracts\FlightImportRepositoryInterface;
use App\Repositories\Contracts\FlightRepositoryInterface;
use App\Repositories\Contracts\TripRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\FlightImportRepository;
use App\Repositories\FlightRepository;
use App\Repositories\TripRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(AirlineRepositoryInterface::class, AirlineRepository::class);
        $this->app->bind(AirportRepositoryInterface::class, AirportRepository::class);
        $this->app->bind(AirportCsvRepositoryInterface::class, AirportCsvRepository::class);
        $this->app->bind(FlightImportRepositoryInterface::class, FlightImportRepository::class);
        $this->app->bind(FlightRepositoryInterface::class, FlightRepository::class);
        $this->app->bind(TripRepositoryInterface::class, TripRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}
