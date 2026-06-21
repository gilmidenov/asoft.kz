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
        User::firstOrCreate(
            ['email' => 'admin@asoft.kz'],
            [
                'name'     => 'Администратор',
                'password' => Hash::make('admin123'),
                'role'     => 'admin',
            ]
        );

        // Вызываем остальные seeders по порядку (важен порядок из-за внешних ключей)
        $this->call([
            CategorySeeder::class,
            VendorSeeder::class,
            ProductSeeder::class,
        ]);
    }
}
