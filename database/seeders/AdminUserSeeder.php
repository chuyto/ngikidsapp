<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (! $email || ! $password) {
            $mensaje = 'ADMIN_EMAIL y ADMIN_PASSWORD no están definidos en el .env. Se omite el admin.';
            Log::warning($mensaje);

            if ($this->command) {
                $this->command->warn($mensaje);
            }

            return;
        }

        // Solo se crea si no existe. Nunca se resetea la contraseña de una
        // cuenta existente: el .env no debe poder sobrescribir credenciales
        // reales en cada despliegue.
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => env('ADMIN_NAME', 'Administrador'),
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ]
        );

        if ($user->wasRecentlyCreated) {
            Log::info('Usuario administrador inicial creado: '.$email);
        }
    }
}
