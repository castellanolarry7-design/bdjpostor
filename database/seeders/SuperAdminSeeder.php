<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;

/**
 * SuperAdminSeeder
 *
 * Crea el usuario super_admin inicial del sistema.
 * Ejecutar UNA SOLA VEZ en producción o en cada fresh migration.
 *
 * Comando: php artisan db:seed --class=SuperAdminSeeder
 *
 * IMPORTANTE: Cambiar las credenciales antes de ejecutar en producción.
 * Mejor aún: usar variables de entorno del .env para las credenciales.
 */
class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => env('SUPER_ADMIN_EMAIL', 'admin@jpstore.com')],
            [
                'id'         => Str::uuid(),
                'tenant_id'  => null, // Super admin no pertenece a ningún tenant
                'name'       => env('SUPER_ADMIN_NAME', 'Super Admin JPStore'),
                'email'      => env('SUPER_ADMIN_EMAIL', 'admin@jpstore.com'),
                'password'   => Hash::make(env('SUPER_ADMIN_PASSWORD', 'JPStore2024!')),
                'role'       => 'super_admin',
                'active'     => true,
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('✓ Super Admin creado exitosamente.');
        $this->command->warn('  Recuerda cambiar la contraseña en producción.');
    }
}
