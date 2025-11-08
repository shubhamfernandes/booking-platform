<?php

namespace App\Providers;

use App\Domain\Bookings\Contracts\BookingRepositoryInterface;
use App\Domain\Bookings\Contracts\BookingServiceInterface;
use App\Domain\Bookings\Repositories\BookingRepository;
use App\Domain\Bookings\Services\BookingService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(BookingRepositoryInterface::class, BookingRepository::class);
        $this->app->bind(BookingServiceInterface::class, BookingService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
