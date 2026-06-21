<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductLicense;
use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $microsoft = Vendor::where('slug', 'microsoft')->first();
        $adobe     = Vendor::where('slug', 'adobe')->first();
        $kaspersky = Vendor::where('slug', 'kaspersky')->first();
        $eset      = Vendor::where('slug', 'eset')->first();
        $autodesk  = Vendor::where('slug', 'autodesk')->first();
        $acronis   = Vendor::where('slug', 'acronis')->first();
        $corel     = Vendor::where('slug', 'corel')->first();

        $office      = Category::where('slug', 'ofisnoe-po')->first();
        $security    = Category::where('slug', 'bezopasnost')->first();
        $design      = Category::where('slug', 'grafika-i-dizajn')->first();
        $infra       = Category::where('slug', 'infrastruktura')->first();
        $cad         = Category::where('slug', 'sapr')->first();
        $antivirus   = Category::where('slug', 'antivirusy')->first();

        $products = [
            [
                'product' => [
                    'name'              => 'Microsoft Office 2024 Home & Business',
                    'slug'              => 'microsoft-office-2024-home-business',
                    'short_description' => 'Word, Excel, PowerPoint, OneNote, Outlook для дома и бизнеса',
                    'description'       => '<p>Microsoft Office 2024 Home &amp; Business — полный набор приложений для работы с документами, таблицами, презентациями и почтой. Бессрочная лицензия на 1 ПК.</p><ul><li>Word 2024</li><li>Excel 2024</li><li>PowerPoint 2024</li><li>Outlook 2024</li><li>OneNote</li></ul>',
                    'category_id'       => $office?->id,
                    'vendor_id'         => $microsoft?->id,
                    'version'           => '2024',
                    'language'          => 'Русский',
                    'delivery_type'     => 'key',
                    'status'            => 'active',
                    'is_hit'            => true,
                    'is_new'            => false,
                    'is_sale'           => false,
                    'price_from'        => 49900,
                ],
                'licenses' => [
                    ['name' => '1 ПК (бессрочно)', 'type' => 'perpetual', 'devices' => '1', 'price' => 49900, 'old_price' => 65000, 'in_stock' => true],
                    ['name' => '2 ПК (бессрочно)', 'type' => 'perpetual', 'devices' => '2', 'price' => 89900, 'old_price' => null,  'in_stock' => true],
                ],
            ],
            [
                'product' => [
                    'name'              => 'Microsoft 365 Personal',
                    'slug'              => 'microsoft-365-personal',
                    'short_description' => 'Подписка на Office 365: Word, Excel, PowerPoint + 1 ТБ OneDrive',
                    'description'       => '<p>Microsoft 365 Personal — подписка на 1 год для 1 человека. Включает все приложения Office, 1 ТБ облачного хранилища OneDrive и постоянные обновления.</p>',
                    'category_id'       => $office?->id,
                    'vendor_id'         => $microsoft?->id,
                    'version'           => '365',
                    'language'          => 'Русский',
                    'delivery_type'     => 'key',
                    'status'            => 'active',
                    'is_hit'            => true,
                    'is_new'            => true,
                    'is_sale'           => true,
                    'price_from'        => 19900,
                ],
                'licenses' => [
                    ['name' => '1 год / 1 пользователь', 'type' => 'subscription', 'devices' => '5', 'duration_months' => 12, 'price' => 19900, 'old_price' => 24900, 'in_stock' => true],
                    ['name' => '1 месяц / 1 пользователь', 'type' => 'subscription', 'devices' => '5', 'duration_months' => 1, 'price' => 2490, 'old_price' => null, 'in_stock' => true],
                ],
            ],
            [
                'product' => [
                    'name'              => 'Windows 11 Pro',
                    'slug'              => 'windows-11-pro',
                    'short_description' => 'Операционная система Windows 11 Professional — лицензионный ключ',
                    'description'       => '<p>Windows 11 Pro — лицензионный ключ активации для операционной системы. Полная функциональность: BitLocker, Remote Desktop, Hyper-V, Windows Sandbox.</p>',
                    'category_id'       => $infra?->id,
                    'vendor_id'         => $microsoft?->id,
                    'version'           => '11',
                    'language'          => 'Русский',
                    'delivery_type'     => 'key',
                    'status'            => 'active',
                    'is_hit'            => true,
                    'is_new'            => false,
                    'is_sale'           => false,
                    'price_from'        => 14900,
                ],
                'licenses' => [
                    ['name' => '1 ПК (OEM)', 'type' => 'perpetual', 'devices' => '1', 'price' => 14900, 'old_price' => null, 'in_stock' => true],
                    ['name' => '1 ПК (Retail)', 'type' => 'perpetual', 'devices' => '1', 'price' => 24900, 'old_price' => null, 'in_stock' => true],
                ],
            ],
            [
                'product' => [
                    'name'              => 'Adobe Photoshop',
                    'slug'              => 'adobe-photoshop',
                    'short_description' => 'Лучший редактор растровой графики — подписка Creative Cloud',
                    'description'       => '<p>Adobe Photoshop — профессиональный редактор растровой графики. Ретушь фотографий, создание иллюстраций, веб-дизайн. Доступ к облачным функциям и обновлениям.</p>',
                    'category_id'       => $design?->id,
                    'vendor_id'         => $adobe?->id,
                    'version'           => 'CC 2024',
                    'language'          => 'Русский',
                    'delivery_type'     => 'key',
                    'status'            => 'active',
                    'is_hit'            => true,
                    'is_new'            => false,
                    'is_sale'           => false,
                    'price_from'        => 29900,
                ],
                'licenses' => [
                    ['name' => '1 год / 1 ПК', 'type' => 'subscription', 'devices' => '2', 'duration_months' => 12, 'price' => 29900, 'old_price' => 39900, 'in_stock' => true],
                ],
            ],
            [
                'product' => [
                    'name'              => 'Adobe Acrobat Pro DC',
                    'slug'              => 'adobe-acrobat-pro-dc',
                    'short_description' => 'Создание, редактирование и защита PDF-документов',
                    'description'       => '<p>Adobe Acrobat Pro DC — лучший инструмент для работы с PDF: создание, редактирование, подпись, защита и конвертация документов.</p>',
                    'category_id'       => $office?->id,
                    'vendor_id'         => $adobe?->id,
                    'version'           => 'DC 2024',
                    'language'          => 'Русский',
                    'delivery_type'     => 'key',
                    'status'            => 'active',
                    'is_hit'            => false,
                    'is_new'            => false,
                    'is_sale'           => true,
                    'price_from'        => 22900,
                ],
                'licenses' => [
                    ['name' => '1 год / 1 ПК', 'type' => 'subscription', 'devices' => '2', 'duration_months' => 12, 'price' => 22900, 'old_price' => 29900, 'in_stock' => true],
                ],
            ],
            [
                'product' => [
                    'name'              => 'Kaspersky Internet Security',
                    'slug'              => 'kaspersky-internet-security',
                    'short_description' => 'Защита от вирусов, фишинга и интернет-угроз',
                    'description'       => '<p>Kaspersky Internet Security — комплексная защита компьютера: антивирус, фаервол, защита онлайн-платежей, родительский контроль.</p>',
                    'category_id'       => $antivirus?->id ?? $security?->id,
                    'vendor_id'         => $kaspersky?->id,
                    'version'           => '2024',
                    'language'          => 'Русский',
                    'delivery_type'     => 'key',
                    'status'            => 'active',
                    'is_hit'            => true,
                    'is_new'            => false,
                    'is_sale'           => false,
                    'price_from'        => 5990,
                ],
                'licenses' => [
                    ['name' => '1 год / 1 ПК',  'type' => 'subscription', 'devices' => '1',  'duration_months' => 12, 'price' => 5990,  'old_price' => 7990,  'in_stock' => true],
                    ['name' => '1 год / 2 ПК',  'type' => 'subscription', 'devices' => '2',  'duration_months' => 12, 'price' => 8990,  'old_price' => 11990, 'in_stock' => true],
                    ['name' => '1 год / 5 ПК',  'type' => 'subscription', 'devices' => '5',  'duration_months' => 12, 'price' => 12990, 'old_price' => null,   'in_stock' => true],
                ],
            ],
            [
                'product' => [
                    'name'              => 'Kaspersky Total Security',
                    'slug'              => 'kaspersky-total-security',
                    'short_description' => 'Максимальная защита: антивирус + менеджер паролей + VPN',
                    'description'       => '<p>Kaspersky Total Security — максимальная защита для всей семьи. Включает менеджер паролей, VPN (200 МБ/день), защиту веб-камеры и микрофона.</p>',
                    'category_id'       => $antivirus?->id ?? $security?->id,
                    'vendor_id'         => $kaspersky?->id,
                    'version'           => '2024',
                    'language'          => 'Русский',
                    'delivery_type'     => 'key',
                    'status'            => 'active',
                    'is_hit'            => false,
                    'is_new'            => true,
                    'is_sale'           => false,
                    'price_from'        => 7990,
                ],
                'licenses' => [
                    ['name' => '1 год / 1 ПК', 'type' => 'subscription', 'devices' => '1', 'duration_months' => 12, 'price' => 7990,  'old_price' => null, 'in_stock' => true],
                    ['name' => '1 год / 3 ПК', 'type' => 'subscription', 'devices' => '3', 'duration_months' => 12, 'price' => 13990, 'old_price' => null, 'in_stock' => true],
                ],
            ],
            [
                'product' => [
                    'name'              => 'ESET NOD32 Antivirus',
                    'slug'              => 'eset-nod32-antivirus',
                    'short_description' => 'Быстрый и лёгкий антивирус — не замедляет компьютер',
                    'description'       => '<p>ESET NOD32 Antivirus — надёжная защита от вирусов с минимальной нагрузкой на систему. Идеален для офисных компьютеров.</p>',
                    'category_id'       => $antivirus?->id ?? $security?->id,
                    'vendor_id'         => $eset?->id,
                    'version'           => '17',
                    'language'          => 'Русский',
                    'delivery_type'     => 'key',
                    'status'            => 'active',
                    'is_hit'            => false,
                    'is_new'            => false,
                    'is_sale'           => true,
                    'price_from'        => 4990,
                ],
                'licenses' => [
                    ['name' => '1 год / 1 ПК', 'type' => 'subscription', 'devices' => '1', 'duration_months' => 12, 'price' => 4990,  'old_price' => 6500, 'in_stock' => true],
                    ['name' => '2 года / 1 ПК', 'type' => 'subscription', 'devices' => '1', 'duration_months' => 24, 'price' => 8490, 'old_price' => null, 'in_stock' => true],
                ],
            ],
            [
                'product' => [
                    'name'              => 'Autodesk AutoCAD 2025',
                    'slug'              => 'autodesk-autocad-2025',
                    'short_description' => 'Профессиональная САПР для 2D и 3D проектирования',
                    'description'       => '<p>AutoCAD 2025 — ведущая программа для автоматизированного проектирования. Используется в архитектуре, машиностроении, строительстве.</p>',
                    'category_id'       => $cad?->id,
                    'vendor_id'         => $autodesk?->id,
                    'version'           => '2025',
                    'language'          => 'Русский',
                    'delivery_type'     => 'key',
                    'status'            => 'active',
                    'is_hit'            => false,
                    'is_new'            => true,
                    'is_sale'           => false,
                    'price_from'        => 149900,
                ],
                'licenses' => [
                    ['name' => '1 год (подписка)', 'type' => 'subscription', 'devices' => '1', 'duration_months' => 12, 'price' => 149900, 'old_price' => null, 'in_stock' => true],
                    ['name' => '3 года (подписка)', 'type' => 'subscription', 'devices' => '1', 'duration_months' => 36, 'price' => 399900, 'old_price' => null, 'in_stock' => true],
                ],
            ],
            [
                'product' => [
                    'name'              => 'Acronis Cyber Protect Home Office',
                    'slug'              => 'acronis-cyber-protect-home-office',
                    'short_description' => 'Резервное копирование + антивирус в одном продукте',
                    'description'       => '<p>Acronis Cyber Protect Home Office (бывший True Image) — надёжное резервное копирование всего диска с защитой от вирусов-шифровальщиков.</p>',
                    'category_id'       => $security?->id,
                    'vendor_id'         => $acronis?->id,
                    'version'           => '2024',
                    'language'          => 'Русский',
                    'delivery_type'     => 'key',
                    'status'            => 'active',
                    'is_hit'            => false,
                    'is_new'            => false,
                    'is_sale'           => false,
                    'price_from'        => 8990,
                ],
                'licenses' => [
                    ['name' => '1 год / 1 ПК',  'type' => 'subscription', 'devices' => '1', 'duration_months' => 12, 'price' => 8990,  'old_price' => null, 'in_stock' => true],
                    ['name' => '1 год / 3 ПК',  'type' => 'subscription', 'devices' => '3', 'duration_months' => 12, 'price' => 14990, 'old_price' => null, 'in_stock' => true],
                    ['name' => '1 год / 5 ПК',  'type' => 'subscription', 'devices' => '5', 'duration_months' => 12, 'price' => 19990, 'old_price' => null, 'in_stock' => true],
                ],
            ],
            [
                'product' => [
                    'name'              => 'CorelDRAW Graphics Suite 2024',
                    'slug'              => 'coreldraw-graphics-suite-2024',
                    'short_description' => 'Векторный редактор для профессиональной графики и дизайна',
                    'description'       => '<p>CorelDRAW Graphics Suite 2024 — профессиональный набор для векторной графики, вёрстки и дизайна. Включает CorelDRAW, Corel Photo-Paint и другие инструменты.</p>',
                    'category_id'       => $design?->id,
                    'vendor_id'         => $corel?->id,
                    'version'           => '2024',
                    'language'          => 'Русский',
                    'delivery_type'     => 'key',
                    'status'            => 'active',
                    'is_hit'            => false,
                    'is_new'            => true,
                    'is_sale'           => false,
                    'price_from'        => 59900,
                ],
                'licenses' => [
                    ['name' => 'Бессрочная',   'type' => 'perpetual',    'devices' => '1', 'price' => 59900, 'old_price' => 79900, 'in_stock' => true],
                    ['name' => '1 год (подписка)', 'type' => 'subscription', 'devices' => '1', 'duration_months' => 12, 'price' => 29900, 'old_price' => null, 'in_stock' => true],
                ],
            ],
            [
                'product' => [
                    'name'              => 'Microsoft SQL Server 2022 Standard',
                    'slug'              => 'microsoft-sql-server-2022-standard',
                    'short_description' => 'Система управления базами данных для бизнеса',
                    'description'       => '<p>Microsoft SQL Server 2022 Standard — корпоративная система управления базами данных. Поддерживает до 24 ядер процессора и 128 ГБ RAM.</p>',
                    'category_id'       => $infra?->id,
                    'vendor_id'         => $microsoft?->id,
                    'version'           => '2022',
                    'language'          => 'Русский',
                    'delivery_type'     => 'key',
                    'status'            => 'active',
                    'is_hit'            => false,
                    'is_new'            => false,
                    'is_sale'           => false,
                    'price_from'        => 299900,
                ],
                'licenses' => [
                    ['name' => 'Лицензия сервера', 'type' => 'perpetual', 'devices' => null, 'price' => 299900, 'old_price' => null, 'in_stock' => false],
                ],
            ],
        ];

        foreach ($products as $item) {
            $product = Product::create($item['product']);
            foreach ($item['licenses'] as $i => $licenseData) {
                $licenseData['sort_order'] = $i;
                if (!isset($licenseData['duration_months'])) {
                    $licenseData['duration_months'] = null;
                }
                if (!isset($licenseData['old_price'])) {
                    $licenseData['old_price'] = null;
                }
                if (!isset($licenseData['devices'])) {
                    $licenseData['devices'] = null;
                }
                ProductLicense::create(array_merge(['product_id' => $product->id], $licenseData));
            }
        }
    }
}
