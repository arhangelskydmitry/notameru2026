<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\WordPress\User;
use App\Models\UserRole;

class GenerateAdminPasswords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:generate-passwords 
                            {--reset : Сбросить все пароли и создать новые}
                            {--show-all : Показать все пароли (только для разработки)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Генерирует пароли для всех пользователей с ролями в админ-панели';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔐 Генерация паролей для пользователей админ-панели...');
        $this->newLine();
        
        // Получаем всех пользователей с ролями
        $usersWithRoles = UserRole::with(['user', 'role'])->get();
        
        if ($usersWithRoles->isEmpty()) {
            $this->error('❌ Пользователи с ролями не найдены!');
            return 1;
        }
        
        $reset = $this->option('reset');
        $showAll = $this->option('show-all');
        $passwordsList = [];
        $superAdminPassword = null;
        
        foreach ($usersWithRoles as $userRole) {
            $user = $userRole->user;
            
            if (!$user) {
                continue;
            }
            
            // Если пароль уже есть и не требуется сброс, пропускаем
            if ($user->admin_password && !$reset) {
                $this->line("⏭️  {$user->display_name} ({$user->user_email}) - пароль уже установлен");
                
                // Для суперадмина показываем пароль
                if ($userRole->role->name === 'super_admin' && $user->admin_password_plain) {
                    $superAdminPassword = [
                        'email' => $user->user_email,
                        'name' => $user->display_name,
                        'password' => $user->admin_password_plain,
                        'role' => $userRole->role->display_name,
                    ];
                }
                
                continue;
            }
            
            // Генерируем безопасный пароль
            $password = $this->generateSecurePassword();
            
            // Сохраняем пароль
            $user->admin_password = Hash::make($password);
            $user->admin_password_plain = $password; // Храним для суперадмина
            $user->save();
            
            $passwordData = [
                'email' => $user->user_email,
                'name' => $user->display_name,
                'password' => $password,
                'role' => $userRole->role->display_name,
                'position' => $userRole->position,
            ];
            
            $passwordsList[] = $passwordData;
            
            // Если это суперадмин, запоминаем отдельно
            if ($userRole->role->name === 'super_admin') {
                $superAdminPassword = $passwordData;
            }
            
            $this->info("✅ {$user->display_name} ({$user->user_email}) - пароль создан");
        }
        
        $this->newLine();
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();
        
        // Показываем пароль суперадмина
        if ($superAdminPassword) {
            $this->info('🔑 ПАРОЛЬ СУПЕРАДМИНИСТРАТОРА:');
            $this->newLine();
            $this->line("   Имя: {$superAdminPassword['name']}");
            $this->line("   Email: {$superAdminPassword['email']}");
            $this->line("   Роль: {$superAdminPassword['role']}");
            $this->line("   Должность: {$superAdminPassword['position']}");
            $this->line('   Пароль: <fg=green;options=bold>' . $superAdminPassword['password'] . '</>');
            $this->newLine();
            $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->newLine();
        }
        
        // Если запрошено, показываем все пароли
        if ($showAll && !empty($passwordsList)) {
            $this->warn('⚠️  ВНИМАНИЕ: Показываются все пароли (только для разработки!)');
            $this->newLine();
            
            $this->table(
                ['Email', 'Имя', 'Роль', 'Пароль'],
                array_map(function($data) {
                    return [
                        $data['email'],
                        $data['name'],
                        $data['role'],
                        $data['password'],
                    ];
                }, $passwordsList)
            );
            
            $this->newLine();
        }
        
        $this->info('✅ Генерация паролей завершена!');
        $this->info('📝 Суперадмин может просмотреть все пароли в админ-панели');
        
        return 0;
    }
    
    /**
     * Генерирует безопасный пароль
     */
    private function generateSecurePassword(): string
    {
        // Генерируем пароль из 12 символов: буквы, цифры и спецсимволы
        $uppercase = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lowercase = 'abcdefghjkmnpqrstuvwxyz';
        $numbers = '23456789';
        $special = '!@#$%*-_+=';
        
        $password = '';
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $special[random_int(0, strlen($special) - 1)];
        
        // Добавляем еще 5 случайных символов
        $allChars = $uppercase . $lowercase . $numbers . $special;
        for ($i = 0; $i < 5; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }
        
        // Перемешиваем символы
        $password = str_shuffle($password);
        
        return $password;
    }
}
