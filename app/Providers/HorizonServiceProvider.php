<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        parent::boot();

        // Si on demande l'URL horizon en production sans les bons identifiants, on déclenche l'invite basic auth
        if (app()->environment('production') && request()->is('horizon*')) {
            if (request()->getUser() !== env('HORIZON_USER') || request()->getPassword() !== env('HORIZON_PASSWORD')) {
                header('WWW-Authenticate: Basic realm="Horizon Dashboard"');
                header('HTTP/1.0 401 Unauthorized');
                echo 'Accès refusé.';
                exit;
            }
        }
    }

    /**
     * Register the Horizon gate.
     *
     * This gate determines who can access Horizon in non-local environments.
     */
    protected function gate()
    {
        Gate::define('viewHorizon', function ($user = null) {
            // En local (Windows), on s'autorise l'accès direct sans mot de passe
            if (app()->environment('local')) {
                return true;
            }

            // En production, on compare ce qui est tapé avec les valeurs du .env
            return request()->getUser() === env('HORIZON_USER') &&
                request()->getPassword() === env('HORIZON_PASSWORD');
        });
    }
}
