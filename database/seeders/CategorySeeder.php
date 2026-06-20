<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // Категории верхнего уровня
        $topCategories = [
            ['name' => 'Офисное ПО',          'sort_order' => 1],
            ['name' => 'Безопасность',         'sort_order' => 2],
            ['name' => 'Графика и дизайн',     'sort_order' => 3],
            ['name' => 'Инфраструктура',       'sort_order' => 4],
            ['name' => 'САПР',                 'sort_order' => 5],
            ['name' => 'Антивирусы',           'sort_order' => 6],
            ['name' => 'Мультимедиа',          'sort_order' => 7],
        ];

        foreach ($topCategories as $cat) {
            // Str::slug() — преобразует "Офисное ПО" → "ofisnoe-po"
            Category::create([
                'name'       => $cat['name'],
                'slug'       => Str::slug($cat['name']),
                'sort_order' => $cat['sort_order'],
                'is_active'  => true,
            ]);
        }

        // Подкатегории для "Офисное ПО" (parent_id = 1)
        $officeId = Category::where('slug', Str::slug('Офисное ПО'))->value('id');
        $subOffice = ['Microsoft Office', 'LibreOffice', 'Р7-Офис', 'МойОфис'];
        foreach ($subOffice as $i => $name) {
            Category::create([
                'name'      => $name,
                'slug'      => Str::slug($name),
                'parent_id' => $officeId,
                'sort_order' => $i + 1,
                'is_active' => true,
            ]);
        }
    }
}
