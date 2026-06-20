<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Создаём администратора
        User::create([
            'name'     => 'Администратор',
            'email'    => 'admin@asoft.kz',
            // Hash::make() — хешируем пароль. Никогда не храним пароли в открытом виде!
            'password' => Hash::make('admin123'),
            'role'     => 'admin',
        ]);

        // Вызываем остальные seeders по порядку (важен порядок из-за внешних ключей)
        $this->call([
            CategorySeeder::class,
            VendorSeeder::class,
            ProductSeeder::class,
        ]);
    }
}
