<?php

use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\AuditoriaController;
use App\Http\Controllers\HijoController;
use App\Http\Controllers\PadreController;
use App\Http\Controllers\RedController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\ServicioController;
use App\Http\Controllers\SupervisorController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\PreventAuthenticatedCaching;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;

// --- RUTAS DE ACCESO (FORZADAS) ---
Route::middleware(['guest'])->group(function () {
    // Forzamos la vista de Login
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

// Ruta raíz: portal interno (invitados ven acceso, equipo ve atajos)
Route::get('/', function () {
    $servicioActivo = \App\Models\Servicio::where('activo', true)->first();

    return view('welcome', compact('servicioActivo'));
});

// Aviso de Privacidad: público (se enlaza desde el registro y el portal)
Route::view('/privacidad', 'privacidad')->name('privacidad');

// --- RUTAS PROTEGIDAS ---
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    PreventAuthenticatedCaching::class,
])->group(function () {

    // Dashboard
    Route::get('/dashboard', function () {
        $servicioActivo = \App\Models\Servicio::where('activo', true)->first();
        $asistenciasActivas = \App\Models\Asistencia::query()
            ->when($servicioActivo, fn ($query) => $query->where('servicio_id', $servicioActivo->id));

        return view('dashboard', [
            'totalPadres' => \App\Models\Padre::count(),
            'totalHijos' => \App\Models\Hijo::count(),
            'servicioActivo' => $servicioActivo,
            'asistenciasHoy' => $servicioActivo ? (clone $asistenciasActivas)->count() : 0,
            'salidasPendientes' => $servicioActivo
                ? (clone $asistenciasActivas)->whereNull('salida_at')->whereHas('registros', fn ($query) => $query->where('estado', 'asistio'))->count()
                : 0,
            'recientes' => \App\Models\Asistencia::with(['padre', 'registros'])->latest('entrada_at')->take(6)->get(),
        ]);
    })->name('dashboard');

    // Padres
    Route::get('/padres', [PadreController::class, 'index'])->name('padres.index')->middleware('can:padres.ver');
    Route::get('/padres/crear', [PadreController::class, 'create'])->name('padres.create')->middleware('can:padres.crear');
    Route::post('/padres', [PadreController::class, 'store'])->name('padres.store')->middleware('can:padres.crear');
    Route::get('/padres/{padre}', [PadreController::class, 'show'])->name('padres.show')->middleware('can:padres.ver');
    Route::get('/padres/{padre}/exportar', [PadreController::class, 'exportar'])->name('padres.exportar')->middleware(['can:padres.ver', 'throttle:reportes-export']);
    Route::post('/padres/{padre}/wallet', [PadreController::class, 'wallet'])->name('padres.wallet')->middleware('can:padres.editar');
    Route::post('/padres/{padre}/wallet/revocar', [PadreController::class, 'walletRevocar'])->name('padres.wallet.revocar')->middleware('can:padres.editar');
    Route::post('/padres/{padre}/revocar-privacidad', [PadreController::class, 'revocarPrivacidad'])->name('padres.revocar')->middleware('can:padres.eliminar');
    Route::get('/padres/{padre}/editar', [PadreController::class, 'edit'])->name('padres.edit')->middleware('can:padres.editar');
    Route::put('/padres/{padre}', [PadreController::class, 'update'])->name('padres.update')->middleware('can:padres.editar');
    Route::delete('/padres/{padre}', [PadreController::class, 'destroy'])->name('padres.destroy')->middleware('can:padres.eliminar');
    Route::post('/padres/{id}/restaurar', [PadreController::class, 'restore'])->name('padres.restore')->middleware('can:padres.editar');
    Route::delete('/padres/{id}/definitivo', [PadreController::class, 'forceDelete'])->name('padres.forceDelete')->middleware('can:padres.eliminar');

    // Hijos
    Route::get('/hijos', [HijoController::class, 'index'])->name('hijos.index')->middleware('can:hijos.ver');
    Route::get('/hijos/crear', [HijoController::class, 'create'])->name('hijos.create')->middleware('can:hijos.crear');
    Route::post('/hijos', [HijoController::class, 'store'])->name('hijos.store')->middleware('can:hijos.crear');
    Route::get('/hijos/{hijo}/editar', [HijoController::class, 'edit'])->name('hijos.edit')->middleware('can:hijos.editar');
    Route::put('/hijos/{hijo}', [HijoController::class, 'update'])->name('hijos.update')->middleware('can:hijos.editar');
    Route::delete('/hijos/{hijo}', [HijoController::class, 'destroy'])->name('hijos.destroy')->middleware('can:hijos.eliminar');
    Route::post('/hijos/{id}/restaurar', [HijoController::class, 'restore'])->name('hijos.restore')->middleware('can:hijos.editar');
    Route::delete('/hijos/{id}/definitivo', [HijoController::class, 'forceDelete'])->name('hijos.forceDelete')->middleware('can:hijos.eliminar');

    // Servicios
    Route::get('/servicios', [ServicioController::class, 'index'])->name('servicios.index')->middleware('can:servicios.ver');
    Route::get('/servicios/crear', [ServicioController::class, 'create'])->name('servicios.create')->middleware('can:servicios.crear');
    Route::post('/servicios', [ServicioController::class, 'store'])->name('servicios.store')->middleware('can:servicios.crear');
    Route::get('/servicios/{servicio}/editar', [ServicioController::class, 'edit'])->name('servicios.edit')->middleware('can:servicios.editar');
    Route::put('/servicios/{servicio}', [ServicioController::class, 'update'])->name('servicios.update')->middleware('can:servicios.editar');
    Route::post('/servicios/{servicio}/activar', [ServicioController::class, 'activar'])->name('servicios.activar')->middleware('can:servicios.editar');
    Route::delete('/servicios/{servicio}', [ServicioController::class, 'destroy'])->name('servicios.destroy')->middleware('can:servicios.eliminar');

    // Redes
    Route::get('/redes', [RedController::class, 'index'])->name('redes.index')->middleware('can:redes.ver');
    Route::get('/redes/crear', [RedController::class, 'create'])->name('redes.create')->middleware('can:redes.crear');
    Route::post('/redes', [RedController::class, 'store'])->name('redes.store')->middleware('can:redes.crear');
    Route::get('/redes/{rede}', [RedController::class, 'show'])->name('redes.show')->middleware('can:redes.ver');
    Route::get('/redes/{rede}/editar', [RedController::class, 'edit'])->name('redes.edit')->middleware('can:redes.editar');
    Route::put('/redes/{rede}', [RedController::class, 'update'])->name('redes.update')->middleware('can:redes.editar');
    Route::delete('/redes/{rede}', [RedController::class, 'destroy'])->name('redes.destroy')->middleware('can:redes.eliminar');

    // Asistencias
    Route::get('/asistencias/create', [AsistenciaController::class, 'create'])->name('asistencias.create')->middleware('can:asistencias.crear');
    Route::post('/asistencias/store', [AsistenciaController::class, 'store'])->name('asistencias.store')->middleware(['can:asistencias.crear', 'throttle:asistencias-store']);
    Route::get('/asistencias/search', [AsistenciaController::class, 'search'])->name('asistencias.search')->middleware(['can:asistencias.crear', 'throttle:asistencias-search']);
    Route::get('/asistencias/{asistencia}/editar', [AsistenciaController::class, 'edit'])->name('asistencias.edit')->middleware('can:asistencias.editar');
    Route::put('/asistencias/{asistencia}', [AsistenciaController::class, 'update'])->name('asistencias.update')->middleware('can:asistencias.editar');
    Route::delete('/asistencias/{asistencia}', [AsistenciaController::class, 'destroy'])->name('asistencias.destroy')->middleware('can:asistencias.eliminar');
    Route::get('/asistencias/reporte-activo', [AsistenciaController::class, 'reporteServicioActivo'])->name('asistencias.reporte-activo')->middleware('can:reportes.ver');

    // Reportes
    Route::get('/reportes/index', [ReporteController::class, 'index'])->name('reportes.index')->middleware('can:reportes.ver');
    Route::get('/reportes/pdf', [ReporteController::class, 'pdf'])->name('reportes.pdf')->middleware(['can:reportes.ver', 'throttle:reportes-export']);
    Route::get('/reportes/csv', [ReporteController::class, 'csv'])->name('reportes.csv')->middleware(['can:reportes.ver', 'throttle:reportes-export']);

    // Supervisores
    Route::get('/supervisores', [SupervisorController::class, 'index'])->name('supervisores.index')->middleware('can:supervisores.ver');
    Route::post('/supervisores', [SupervisorController::class, 'store'])->name('supervisores.store')->middleware('can:supervisores.crear');
    Route::put('/supervisores/{supervisor}', [SupervisorController::class, 'update'])->name('supervisores.update')->middleware('can:supervisores.editar');
    Route::post('/supervisores/{supervisor}/estado', [SupervisorController::class, 'toggle'])->name('supervisores.toggle')->middleware('can:supervisores.editar');
    Route::delete('/supervisores/{supervisor}', [SupervisorController::class, 'destroy'])->name('supervisores.destroy')->middleware('can:supervisores.eliminar');

    // Usuarios y permisos
    Route::get('/usuarios', [UserController::class, 'index'])->name('usuarios.index')->middleware('can:usuarios.ver');
    Route::get('/usuarios/crear', [UserController::class, 'create'])->name('usuarios.create')->middleware('can:usuarios.crear');
    Route::post('/usuarios', [UserController::class, 'store'])->name('usuarios.store')->middleware('can:usuarios.crear');
    Route::get('/usuarios/{user}/editar', [UserController::class, 'edit'])->name('usuarios.edit')->middleware('can:usuarios.editar');
    Route::put('/usuarios/{user}', [UserController::class, 'update'])->name('usuarios.update')->middleware('can:usuarios.editar');
    Route::delete('/usuarios/{user}', [UserController::class, 'destroy'])->name('usuarios.destroy')->middleware('can:usuarios.eliminar');

    // Auditoría (solo administradores)
    Route::get('/auditoria', [AuditoriaController::class, 'index'])->name('auditoria.index')->middleware('can:auditoria.ver');
    Route::get('/auditoria/exportar', [AuditoriaController::class, 'exportar'])->name('auditoria.exportar')->middleware(['can:auditoria.ver', 'throttle:reportes-export']);

    // Informacion
    Route::view('/ayuda', 'ayuda.index')->name('ayuda')->middleware(['auth']);
});
