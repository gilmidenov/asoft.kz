<?php

namespace Database\Seeders;

use App\Models\Vendor;
use Illuminate\Database\Seeder;

class VendorSeeder extends Seeder
{
    public function run(): void
    {
        $vendors = [
            ['name' => 'Microsoft',       'slug' => 'microsoft',       'description' => 'Мировой лидер в разработке программного обеспечения', 'is_active' => true],
            ['name' => 'Adobe',           'slug' => 'adobe',           'description' => 'Программы для дизайна, видео и работы с PDF',          'is_active' => true],
            ['name' => 'Kaspersky',       'slug' => 'kaspersky',       'description' => 'Антивирусное программное обеспечение',                  'is_active' => true],
            ['name' => 'ESET',            'slug' => 'eset',            'description' => 'Антивирус и защита компьютера',                        'is_active' => true],
            ['name' => 'Autodesk',        'slug' => 'autodesk',        'description' => 'САПР и 3D-моделирование',                              'is_active' => true],
            ['name' => 'Acronis',         'slug' => 'acronis',         'description' => 'Резервное копирование и восстановление данных',         'is_active' => true],
            ['name' => '1C',              'slug' => '1c',              'description' => 'Системы автоматизации бизнеса',                        'is_active' => true],
            ['name' => 'Corel',           'slug' => 'corel',           'description' => 'Графические редакторы и офисные решения',              'is_active' => true],
        ];

        foreach ($vendors as $vendor) {
            Vendor::create($vendor);
        }
    }
}
