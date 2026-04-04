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
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AirlineRepositoryInterface::class, AirlineRepository::class);
        $this->app->bind(AirportRepositoryInterface::class, AirportRepository::class);
        $this->app->bind(FlightRepositoryInterface::class, FlightRepository::class);
        $this->app->bind(TripRepositoryInterface::class, TripRepository::class);
        $this->app->bind(AirportCsvRepositoryInterface::class, AirportCsvRepository::class);
        $this->app->bind(FlightImportRepositoryInterface::class, FlightImportRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
