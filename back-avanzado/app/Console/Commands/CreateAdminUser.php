<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    protected $signature = 'make:admin 
                            {email : El correo electrónico del administrador} 
                            {--name=Admin : El nombre completo} 
                            {--password= : La contraseña del usuario}';

    protected $description = 'Crea o promueve un usuario existente al rol de Administrador (Operación CLI segura)';

    public function handle(): int
    {
        $email = trim($this->argument('email'));
        $name = trim($this->option('name'));
        $password = $this->option('password') ?: $this->secret('Ingresa la contraseña para el Administrador');

        if (empty($password)) {
            $this->error('La contraseña no puede estar vacía.');
            return Command::FAILURE;
        }

        // Asignación explícita de propiedades para ignorar restricciones de $fillable
        $user = User::firstOrNew(['email' => $email]);

        if (! $user->exists) {
            $user->name = $name;
            $user->password = Hash::make($password);
            $this->info("Creando nuevo registro para: {$email}");
        } else {
            $this->info("Promoviendo usuario existente: {$email}");
        }

        $user->role = 'admin'; // Asignación directa y aislada del ORM
        $user->save();

        $this->info("¡Éxito! El usuario {$email} fue configurado como Administrador.");

        return Command::SUCCESS;
    }
}