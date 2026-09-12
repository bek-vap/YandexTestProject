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
        // updateOrCreate: если запустить сидер ещё раз, второй такой же юзер не создастся.
        // пароль хешируется сам (cast 'hashed')
        User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'password' => 'password',
            ],
        );
    }
}
