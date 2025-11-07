<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BannerZoneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $zones = [
            [
                'name' => 'header',
                'display_name' => 'Шапка сайта',
                'description' => 'Горизонтальный баннер в верхней части страницы',
                'max_banners' => 3,
                'recommended_width' => 728,
                'recommended_height' => 90,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'sidebar-top',
                'display_name' => 'Сайдбар (верх)',
                'description' => 'Баннер в верхней части правого сайдбара',
                'max_banners' => 5,
                'recommended_width' => 300,
                'recommended_height' => 250,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'sidebar-middle',
                'display_name' => 'Сайдбар (середина)',
                'description' => 'Баннер в середине правого сайдбара',
                'max_banners' => 5,
                'recommended_width' => 300,
                'recommended_height' => 250,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'content-top',
                'display_name' => 'Контент (верх)',
                'description' => 'Баннер перед основным контентом',
                'max_banners' => 3,
                'recommended_width' => 728,
                'recommended_height' => 90,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'content-middle',
                'display_name' => 'Контент (середина)',
                'description' => 'Баннер в середине контента',
                'max_banners' => 5,
                'recommended_width' => 336,
                'recommended_height' => 280,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'footer',
                'display_name' => 'Подвал сайта',
                'description' => 'Горизонтальный баннер в нижней части страницы',
                'max_banners' => 3,
                'recommended_width' => 728,
                'recommended_height' => 90,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('banner_zones')->insert($zones);
    }
}
