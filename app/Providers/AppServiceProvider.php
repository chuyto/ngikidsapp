<?php

namespace App\Providers;

use App\Models\Servicio;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    // app/Providers/AppServiceProvider.php

    public function boot(): void
    {
        if (app()->environment('production')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        // Política mínima de contraseñas para Fortify, Jetstream y el CRUD
        // de usuarios. Se aplica también al restablecer por correo.
        Password::defaults(fn () => Password::min(10)->mixedCase()->numbers());

        // Permisos Spatie: un permiso "modulo.accion" funciona como gate nativo.
        Gate::before(function ($user, $ability) {
            return $user?->hasRole('Administrador') ? true : null;
        });

        // La bitácora también registra accesos, no solo cambios de datos.
        Event::listen(Login::class, function (Login $event) {
            AuditLogger::logForUser(
                $event->user->getKey(),
                'sesion.iniciada',
                $event->user,
                'Inicio de sesión de '.$event->user->email,
            );
        });

        Event::listen(Logout::class, function (Logout $event) {
            if ($event->user) {
                // Al eliminar la propia cuenta, Jetstream borra al usuario
                // antes de cerrar la sesión. Conservamos el movimiento con
                // user_id nulo para no violar la llave foránea.
                $userId = User::whereKey($event->user->getKey())->exists()
                    ? $event->user->getKey()
                    : null;

                AuditLogger::logForUser(
                    $userId,
                    'sesion.cerrada',
                    $event->user,
                    'Cierre de sesión de '.$event->user->email,
                );
            }
        });

        Event::listen(Failed::class, function (Failed $event) {
            $email = $event->credentials['email'] ?? null;

            AuditLogger::logForUser(
                $event->user?->getKey(),
                'sesion.fallida',
                null,
                $email
                    ? 'Intento fallido de inicio de sesión para '.$email
                    : 'Intento fallido de inicio de sesión',
            );
        });

        // Límites para endpoints costosos: búsqueda de credenciales, subida
        // de firmas y generación de reportes.
        RateLimiter::for('asistencias-search', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('asistencias-store', function (Request $request) {
            return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('reportes-export', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        // Pagination
        Paginator::useTailwind();

        // Compartir el servicio activo SOLO si el usuario está autenticado
        view()->composer('*', function ($view) {
            if (auth()->check()) {
                try {
                    $servicioActivo = \App\Models\Servicio::where('activo', true)->first();
                    $view->with('servicioActivo', $servicioActivo);
                } catch (\Exception $e) {
                    $view->with('servicioActivo', null);
                }
            } else {
                // Si es un "guest", mandamos null explícitamente
                $view->with('servicioActivo', null);
            }
        });
    }
}
