<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            ['title' => 'О компании',               'slug' => 'about',        'sort_order' => 1],
            ['title' => 'Решения и Компетенции',     'slug' => 'solutions',    'sort_order' => 2],
            ['title' => 'Разработка',                'slug' => 'development',  'sort_order' => 3],
            ['title' => 'Проекты',                   'slug' => 'projects',     'sort_order' => 4],
            ['title' => 'Новости и События',         'slug' => 'news',         'sort_order' => 5],
            ['title' => 'Нам доверяют',              'slug' => 'clients',      'sort_order' => 6],
            ['title' => 'Карьера',                   'slug' => 'career',       'sort_order' => 7],
            ['title' => 'Сертификаты',               'slug' => 'certificates', 'sort_order' => 8],
            ['title' => 'Реквизиты Компании',        'slug' => 'requisites',   'sort_order' => 9],
        ];

        foreach ($pages as $page) {
            Page::firstOrCreate(['slug' => $page['slug']], array_merge($page, ['is_active' => true]));
        }
    }
}
