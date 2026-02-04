<?php

namespace App\Providers;

use App\Models\Sistemas_IT\Ticket;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        // Definir gates para acceso a paneles por posición
        Gate::define('ver_rh', function ($user) {
            return $user->isRH();
        });

        Gate::define('ver_logistica', function ($user) {
            return $user->isLogistica();
        });

        View::composer('components.authenticated-actions', function ($view) {
            if (!Auth::check() || Auth::user()->isAdmin()) {
                $view->with([
                    'notifications' => collect(),
                    'notificationsCount' => 0,
                ]);

                return;
            }

            $tickets = Auth::user()
                ->tickets()
                ->where('user_has_updates', true)
                ->latest('user_notified_at')
                ->latest('updated_at')
                ->limit(10)
                ->get([
                    'id',
                    'folio',
                    'user_notification_summary',
                    'user_notified_at',
                    'updated_at',
                    'estado',
                    'tipo_problema',
                ]);

            $notifications = $tickets->map(function (Ticket $ticket) {
                return [
                    'id' => $ticket->id,
                    'folio' => $ticket->folio,
                    'summary' => $ticket->user_notification_summary
                        ?: 'Tu ticket tiene nuevas actualizaciones del equipo de TI.',
                    'timestamp' => optional($ticket->user_notified_at ?? $ticket->updated_at)->diffForHumans(),
                    'estado' => ucfirst(str_replace('_', ' ', $ticket->estado)),
                    'tipo' => ucfirst($ticket->tipo_problema),
                    'link' => route('tickets.mis-tickets') . '#ticket-' . $ticket->id,
                    'acknowledgeUrl' => route('tickets.acknowledge', $ticket),
                ];
            });

            $view->with([
                'notifications' => $notifications,
                'notificationsCount' => $notifications->count(),
            ]);
        });
    }
}
