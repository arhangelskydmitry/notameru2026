<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use App\Models\UserRole;
use App\Models\WordPress\User;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Создаем роли
        $superAdmin = Role::create([
            'name' => 'super_admin',
            'display_name' => 'Суперадминистратор',
            'description' => 'Полный доступ ко всем функциям системы',
            'level' => 100,
        ]);

        $editor = Role::create([
            'name' => 'editor',
            'display_name' => 'Главный редактор',
            'description' => 'Управление контентом, пользователями, категориями и меню',
            'level' => 50,
        ]);

        $author = Role::create([
            'name' => 'author',
            'display_name' => 'Автор',
            'description' => 'Создание и редактирование собственных статей',
            'level' => 10,
        ]);

        // Создаем права доступа
        $permissions = [
            // Посты
            ['name' => 'view_posts', 'display_name' => 'Просмотр постов', 'group' => 'posts'],
            ['name' => 'create_posts', 'display_name' => 'Создание постов', 'group' => 'posts'],
            ['name' => 'edit_own_posts', 'display_name' => 'Редактирование своих постов', 'group' => 'posts'],
            ['name' => 'edit_all_posts', 'display_name' => 'Редактирование всех постов', 'group' => 'posts'],
            ['name' => 'delete_own_posts', 'display_name' => 'Удаление своих постов', 'group' => 'posts'],
            ['name' => 'delete_all_posts', 'display_name' => 'Удаление всех постов', 'group' => 'posts'],
            ['name' => 'publish_posts', 'display_name' => 'Публикация постов', 'group' => 'posts'],

            // Страницы
            ['name' => 'view_pages', 'display_name' => 'Просмотр страниц', 'group' => 'pages'],
            ['name' => 'edit_pages', 'display_name' => 'Редактирование страниц', 'group' => 'pages'],
            ['name' => 'delete_pages', 'display_name' => 'Удаление страниц', 'group' => 'pages'],

            // Пользователи
            ['name' => 'view_users', 'display_name' => 'Просмотр пользователей', 'group' => 'users'],
            ['name' => 'create_users', 'display_name' => 'Создание пользователей', 'group' => 'users'],
            ['name' => 'edit_users', 'display_name' => 'Редактирование пользователей', 'group' => 'users'],
            ['name' => 'delete_users', 'display_name' => 'Удаление пользователей', 'group' => 'users'],
            ['name' => 'assign_roles', 'display_name' => 'Назначение ролей', 'group' => 'users'],
            ['name' => 'edit_own_profile', 'display_name' => 'Редактирование своего профиля', 'group' => 'users'],

            // Категории
            ['name' => 'view_categories', 'display_name' => 'Просмотр категорий', 'group' => 'categories'],
            ['name' => 'edit_categories', 'display_name' => 'Редактирование категорий', 'group' => 'categories'],
            ['name' => 'delete_categories', 'display_name' => 'Удаление категорий', 'group' => 'categories'],

            // Меню
            ['name' => 'view_menu', 'display_name' => 'Просмотр меню', 'group' => 'menu'],
            ['name' => 'edit_menu', 'display_name' => 'Редактирование меню', 'group' => 'menu'],

            // Баннеры
            ['name' => 'view_banners', 'display_name' => 'Просмотр баннеров', 'group' => 'banners'],
            ['name' => 'edit_banners', 'display_name' => 'Редактирование баннеров', 'group' => 'banners'],

            // Аналитика
            ['name' => 'view_analytics', 'display_name' => 'Просмотр аналитики', 'group' => 'analytics'],
            ['name' => 'view_own_analytics', 'display_name' => 'Просмотр своей аналитики', 'group' => 'analytics'],
            ['name' => 'view_all_analytics', 'display_name' => 'Просмотр всей аналитики', 'group' => 'analytics'],

            // Настройки и бекапы
            ['name' => 'view_settings', 'display_name' => 'Просмотр настроек', 'group' => 'settings'],
            ['name' => 'edit_settings', 'display_name' => 'Редактирование настроек', 'group' => 'settings'],
            ['name' => 'manage_backups', 'display_name' => 'Управление бекапами', 'group' => 'settings'],
            ['name' => 'view_activity_log', 'display_name' => 'Просмотр истории действий', 'group' => 'settings'],
        ];

        foreach ($permissions as $permData) {
            Permission::create($permData);
        }

        // Назначаем права ролям

        // Суперадминистратор - все права (проверка в коде через isSuperAdmin)
        // Можно не назначать, так как в User::hasPermission есть проверка

        // Главный редактор
        $editorPermissions = Permission::whereIn('name', [
            'view_posts', 'create_posts', 'edit_own_posts', 'edit_all_posts', 
            'delete_own_posts', 'delete_all_posts', 'publish_posts',
            'view_pages', // страницы не редактирует по ТЗ
            'view_users', 'create_users', 'edit_users', // может управлять авторами
            'view_categories', 'edit_categories', 'delete_categories',
            'view_menu', 'edit_menu',
            'view_analytics', 'view_all_analytics',
            'edit_own_profile',
        ])->get();
        $editor->permissions()->attach($editorPermissions);

        // Автор
        $authorPermissions = Permission::whereIn('name', [
            'view_posts', 'create_posts', 'edit_own_posts', 
            'delete_own_posts', 'publish_posts',
            'view_categories',
            'view_own_analytics',
            'edit_own_profile',
        ])->get();
        $author->permissions()->attach($authorPermissions);

        // Назначаем роли пользователям

        // Суперадминистратор - d.arhangelsky@gmail.com
        $superAdminUser = User::where('user_email', 'd.arhangelsky@gmail.com')->first();
        if ($superAdminUser) {
            UserRole::create([
                'user_id' => $superAdminUser->ID,
                'role_id' => $superAdmin->id,
                'position' => 'Директор',
            ]);
        }

        // Главный редактор - gp-99@ya.ru
        $editorUser = User::where('user_email', 'gp-99@ya.ru')->first();
        if ($editorUser) {
            UserRole::create([
                'user_id' => $editorUser->ID,
                'role_id' => $editor->id,
                'position' => 'Главный редактор',
            ]);
        }

        // Остальных пользователей делаем авторами
        $otherUsers = User::whereNotIn('user_email', [
            'd.arhangelsky@gmail.com',
            'gp-99@ya.ru'
        ])->get();

        foreach ($otherUsers as $user) {
            UserRole::create([
                'user_id' => $user->ID,
                'role_id' => $author->id,
                'position' => 'Автор',
            ]);
        }

        $this->command->info('✅ Роли и права доступа созданы успешно!');
        $this->command->info('✅ Суперадминистратор: ' . ($superAdminUser ? $superAdminUser->display_name : 'не найден'));
        $this->command->info('✅ Главный редактор: ' . ($editorUser ? $editorUser->display_name : 'не найден'));
        $this->command->info('✅ Авторов назначено: ' . $otherUsers->count());
    }
}
