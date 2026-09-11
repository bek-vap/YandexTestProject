<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Один сид-пользователь для входа (регистрации в задании нет).
        // updateOrCreate — идемпотентно: повторный запуск сидера не создаст дубль,
        // а обновит существующего по email. Пароль хешируется автоматически (cast 'hashed').
        User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'password' => 'password',
            ],
        );
    }
}
