<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MenuItem;

class MenuItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $menuItems = [
            ['title' => 'Новости', 'slug' => 'news', 'type' => 'category', 'order' => 1],
            ['title' => 'Анонсы', 'slug' => 'anonsy', 'type' => 'category', 'order' => 2],
            ['title' => 'Интервью', 'slug' => 'interview', 'type' => 'category', 'order' => 3],
            ['title' => 'Спорт', 'slug' => 'sport', 'type' => 'category', 'order' => 4],
            ['title' => 'Психология', 'slug' => 'psihologiya', 'type' => 'category', 'order' => 5],
            ['title' => 'Общество', 'slug' => 'obshhestvo', 'type' => 'category', 'order' => 6],
            ['title' => 'День', 'slug' => 'den', 'type' => 'category', 'order' => 7],
            ['title' => 'Биографии', 'slug' => 'biografii', 'type' => 'category', 'order' => 8],
            ['title' => 'Редакция', 'slug' => 'redakciya', 'type' => 'page', 'order' => 9],
        ];

        foreach ($menuItems as $item) {
            MenuItem::create($item);
        }
    }
}
