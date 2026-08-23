<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CreateAdmin extends Command
{
    protected $signature = 'admin:create';

    protected $description = 'Cria um administrador da Gift Lab';

    public function handle(): int
    {
        $name = $this->ask('Nome');
        $email = $this->ask('E-mail');
        $password = $this->secret('Senha (mínimo 8 caracteres)');
        if (strlen((string) $password) < 8) {
            $this->error('A senha deve ter ao menos 8 caracteres.');

            return self::FAILURE;
        }User::updateOrCreate(['email' => $email], ['name' => $name, 'password' => $password, 'is_admin' => true]);
        $this->info('Administrador criado com sucesso.');

        return self::SUCCESS;
    }
}
