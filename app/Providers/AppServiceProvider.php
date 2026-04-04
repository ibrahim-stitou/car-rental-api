<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Repository bindings
        $this->app->bind(
            \App\Modules\Agency\Repositories\AgencyRepository::class,
            fn() => new \App\Modules\Agency\Repositories\AgencyRepository(new \App\Models\Agency())
        );
        $this->app->bind(
            \App\Modules\Vehicle\Repositories\VehicleRepository::class,
            fn() => new \App\Modules\Vehicle\Repositories\VehicleRepository(new \App\Models\Vehicle())
        );
        $this->app->bind(
            \App\Modules\Client\Repositories\ClientRepository::class,
            fn() => new \App\Modules\Client\Repositories\ClientRepository(new \App\Models\Client())
        );
        $this->app->bind(
            \App\Modules\Reservation\Repositories\ReservationRepository::class,
            fn() => new \App\Modules\Reservation\Repositories\ReservationRepository(new \App\Models\Reservation())
        );
        $this->app->bind(
            \App\Modules\User\Repositories\UserRepository::class,
            fn() => new \App\Modules\User\Repositories\UserRepository(new \App\Models\User())
        );
        $this->app->bind(
            \App\Modules\Insurance\Repositories\InsuranceRepository::class,
            fn() => new \App\Modules\Insurance\Repositories\InsuranceRepository(new \App\Models\Insurance())
        );
        $this->app->bind(
            \App\Modules\Maintenance\Repositories\MaintenanceRepository::class,
            fn() => new \App\Modules\Maintenance\Repositories\MaintenanceRepository(new \App\Models\Maintenance())
        );
        $this->app->bind(
            \App\Modules\TechnicalInspection\Repositories\TechnicalInspectionRepository::class,
            fn() => new \App\Modules\TechnicalInspection\Repositories\TechnicalInspectionRepository(new \App\Models\TechnicalInspection())
        );
        $this->app->bind(
            \App\Modules\Vignette\Repositories\VignetteRepository::class,
            fn() => new \App\Modules\Vignette\Repositories\VignetteRepository(new \App\Models\Vignette())
        );
        $this->app->bind(
            \App\Modules\Billing\Repositories\BillingRepository::class,
            fn() => new \App\Modules\Billing\Repositories\BillingRepository(new \App\Models\BillingDocument())
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register custom artisan commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Modules\Notification\Commands\SendScheduledAlerts::class,
            ]);
        }
    }
}
