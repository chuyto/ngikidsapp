<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PadreController;
use App\Http\Controllers\HijoController;
use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\ServicioController;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    // Ruta para el dashboard
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // Definición de las rutas para padres e hijos
    Route::resource('padres', PadreController::class);
    Route::resource('servicios', ServicioController::class);

    Route::get('/asistencias/create', [AsistenciaController::class, 'create'])->name('asistencias.create');
    Route::post('/asistencias/store', [AsistenciaController::class, 'store'])->name('asistencias.store');
    Route::get('/asistencias/show', [AsistenciaController::class, 'show'])->name('asistencias.show');

    Route::get('/asistencias/search', [AsistenciaController::class, 'search'])->name('asistencias.search');

});
