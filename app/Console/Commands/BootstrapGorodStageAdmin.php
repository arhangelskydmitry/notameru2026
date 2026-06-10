<?php

namespace App\Console\Commands;

use App\Models\Permission;
use App\Models\Role;
use App\Models\UserRole;
use App\Models\WordPress\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class BootstrapGorodStageAdmin extends Command
{
    protected $signature = 'gorod:bootstrap-stage-admin
                            {--login=kira : WordPress login to promote}
                            {--email= : WordPress email to promote}
                            {--password=LocalPass!2026 : Local admin password for notaadmin}
                            {--role=super_admin : super_admin|editor|author}';

    protected $description = 'Bootstrap a local gorod-magazine admin inside notamerularavel';

    public function handle(): int
    {
        $roleName = (string) $this->option('role');
        $password = (string) $this->option('password');

        $role = $this->ensureRolesAndPermissions($roleName);
        $user = $this->resolveUser();

        if (! $user) {
            $this->error('WordPress user not found for the requested login/email.');
            return self::FAILURE;
        }

        $user->admin_account_active = true;
        $user->admin_password = Hash::make($password);
        $user->admin_password_plain = null;
        $user->save();

        UserRole::updateOrCreate(
            ['user_id' => $user->ID],
            [
                'role_id' => $role->id,
                'position' => $role->display_name,
                'custom_permissions' => [],
                'allowed_categories' => [],
            ]
        );

        $this->info("Bootstrap complete for {$user->display_name} ({$user->user_email})");
        $this->line("notaadmin login: {$user->user_email}");
        $this->line("notaadmin password: {$password}");

        return self::SUCCESS;
    }

    private function resolveUser(): ?User
    {
        $email = (string) $this->option('email');
        if ($email !== '') {
            $user = User::where('user_email', $email)->first();
            if ($user) {
                return $user;
            }
        }

        $login = (string) $this->option('login');
        return User::where('user_login', $login)->first();
    }

    private function ensureRolesAndPermissions(string $targetRole): Role
    {
        $roles = [
            Role::SUPER_ADMIN => [
                'display_name' => 'Суперадминистратор',
                'description' => 'Полный доступ ко всем функциям системы',
                'level' => 100,
            ],
            Role::EDITOR => [
                'display_name' => 'Главный редактор',
                'description' => 'Управление контентом и редакторскими сценариями',
                'level' => 50,
            ],
            Role::AUTHOR => [
                'display_name' => 'Автор',
                'description' => 'Создание и редактирование собственных статей',
                'level' => 10,
            ],
        ];

        $permissions = [
            ['name' => 'view_posts', 'display_name' => 'Просмотр постов', 'group' => 'posts'],
            ['name' => 'create_posts', 'display_name' => 'Создание постов', 'group' => 'posts'],
            ['name' => 'edit_own_posts', 'display_name' => 'Редактирование своих постов', 'group' => 'posts'],
            ['name' => 'edit_all_posts', 'display_name' => 'Редактирование всех постов', 'group' => 'posts'],
            ['name' => 'delete_own_posts', 'display_name' => 'Удаление своих постов', 'group' => 'posts'],
            ['name' => 'delete_all_posts', 'display_name' => 'Удаление всех постов', 'group' => 'posts'],
            ['name' => 'publish_posts', 'display_name' => 'Публикация постов', 'group' => 'posts'],
            ['name' => 'view_pages', 'display_name' => 'Просмотр страниц', 'group' => 'pages'],
            ['name' => 'edit_pages', 'display_name' => 'Редактирование страниц', 'group' => 'pages'],
            ['name' => 'delete_pages', 'display_name' => 'Удаление страниц', 'group' => 'pages'],
            ['name' => 'view_users', 'display_name' => 'Просмотр пользователей', 'group' => 'users'],
            ['name' => 'create_users', 'display_name' => 'Создание пользователей', 'group' => 'users'],
            ['name' => 'edit_users', 'display_name' => 'Редактирование пользователей', 'group' => 'users'],
            ['name' => 'delete_users', 'display_name' => 'Удаление пользователей', 'group' => 'users'],
            ['name' => 'assign_roles', 'display_name' => 'Назначение ролей', 'group' => 'users'],
            ['name' => 'edit_own_profile', 'display_name' => 'Редактирование своего профиля', 'group' => 'users'],
            ['name' => 'view_categories', 'display_name' => 'Просмотр категорий', 'group' => 'categories'],
            ['name' => 'edit_categories', 'display_name' => 'Редактирование категорий', 'group' => 'categories'],
            ['name' => 'delete_categories', 'display_name' => 'Удаление категорий', 'group' => 'categories'],
            ['name' => 'view_menu', 'display_name' => 'Просмотр меню', 'group' => 'menu'],
            ['name' => 'edit_menu', 'display_name' => 'Редактирование меню', 'group' => 'menu'],
            ['name' => 'view_banners', 'display_name' => 'Просмотр баннеров', 'group' => 'banners'],
            ['name' => 'edit_banners', 'display_name' => 'Редактирование баннеров', 'group' => 'banners'],
            ['name' => 'view_analytics', 'display_name' => 'Просмотр аналитики', 'group' => 'analytics'],
            ['name' => 'view_own_analytics', 'display_name' => 'Просмотр своей аналитики', 'group' => 'analytics'],
            ['name' => 'view_all_analytics', 'display_name' => 'Просмотр всей аналитики', 'group' => 'analytics'],
            ['name' => 'view_settings', 'display_name' => 'Просмотр настроек', 'group' => 'settings'],
            ['name' => 'edit_settings', 'display_name' => 'Редактирование настроек', 'group' => 'settings'],
            ['name' => 'manage_backups', 'display_name' => 'Управление бекапами', 'group' => 'settings'],
            ['name' => 'view_activity_log', 'display_name' => 'Просмотр истории действий', 'group' => 'settings'],
        ];

        foreach ($roles as $name => $data) {
            Role::updateOrCreate(['name' => $name], $data);
        }

        foreach ($permissions as $data) {
            Permission::updateOrCreate(['name' => $data['name']], $data);
        }

        $editorPermissionNames = [
            'view_posts', 'create_posts', 'edit_own_posts', 'edit_all_posts',
            'delete_own_posts', 'delete_all_posts', 'publish_posts',
            'view_pages', 'view_users', 'create_users', 'edit_users',
            'view_categories', 'edit_categories', 'delete_categories',
            'view_menu', 'edit_menu', 'view_analytics', 'view_all_analytics',
            'edit_own_profile',
        ];

        $authorPermissionNames = [
            'view_posts', 'create_posts', 'edit_own_posts',
            'delete_own_posts', 'publish_posts', 'view_categories',
            'view_own_analytics', 'edit_own_profile',
        ];

        $editor = Role::where('name', Role::EDITOR)->firstOrFail();
        $author = Role::where('name', Role::AUTHOR)->firstOrFail();

        $editor->permissions()->sync(Permission::whereIn('name', $editorPermissionNames)->pluck('id')->all());
        $author->permissions()->sync(Permission::whereIn('name', $authorPermissionNames)->pluck('id')->all());

        return Role::where('name', $targetRole)->firstOrFail();
    }
}
