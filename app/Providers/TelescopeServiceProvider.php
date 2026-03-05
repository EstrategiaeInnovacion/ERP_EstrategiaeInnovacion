<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Telescope::night();

        $this->hideSensitiveRequestDetails();

        $isLocal = $this->app->environment('local');

        Telescope::filter(function (IncomingEntry $entry) {
            return true; // Forzar registro en cualquier entorno local
        });
    }

    /**
     * Prevent sensitive request details from being logged by Telescope.
     */
    protected function hideSensitiveRequestDetails(): void
    {
        if ($this->app->environment('local')) {
            return;
        }

        Telescope::hideRequestParameters(['_token']);

        Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
        ]);
    }

    /**
     * Register the Telescope gate.
     *
     * This gate determines who can access Telescope in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewTelescope', function ($user) {
            // Debe ser rol admin
            if (!$user->hasRole('admin')) {
                return false;
            }

            // Debe existir su relación de empleado
            if (!$user->empleado) {
                return false;
            }

            // Debe estar en el departamento de Sistemas/TI
            $posicion = strtolower(trim($user->empleado->posicion ?? ''));
            return str_contains($posicion, ' ti')
            || str_contains($posicion, 'ti ')
            || $posicion === 'ti'
            || $posicion === 'it'
            || str_contains($posicion, 'sistemas');
        });
    }
}
