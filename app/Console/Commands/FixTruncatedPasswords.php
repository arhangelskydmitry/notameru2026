<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WordPress\User;
use Illuminate\Support\Facades\Hash;

class FixTruncatedPasswords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:fix-passwords';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Исправляет обрезанные bcrypt хеши в admin_password';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔧 Исправление обрезанных паролей...');
        $this->newLine();
        
        // Находим пользователей с обрезанными admin_password
        $users = User::whereNotNull('admin_password')
            ->whereNotNull('admin_password_plain')
            ->get();
        
        if ($users->isEmpty()) {
            $this->info('✅ Нет пользователей с admin_password_plain');
            return 0;
        }
        
        $this->info("Найдено пользователей: {$users->count()}");
        $this->newLine();
        
        $fixed = 0;
        $skipped = 0;
        
        $bar = $this->output->createProgressBar($users->count());
        $bar->start();
        
        foreach ($users as $user) {
            // Проверяем длину admin_password
            $adminPasswordLength = strlen($user->admin_password);
            
            // Bcrypt хеш должен быть 60 символов
            if ($adminPasswordLength < 60 && $user->admin_password_plain) {
                // Создаем правильный bcrypt хеш из plain пароля
                $newHash = Hash::make($user->admin_password_plain);
                $user->admin_password = $newHash;
                $user->save();
                $fixed++;
            } else {
                $skipped++;
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);
        
        $this->info('✅ Исправление завершено!');
        $this->line("   Исправлено: {$fixed}");
        $this->line("   Пропущено: {$skipped}");
        
        $this->newLine();
        $this->warn('⚠️  Рекомендация: После успешного входа удалите admin_password_plain');
        $this->comment('   Это можно сделать через SQL или добавить в профиль пользователя');
        
        return 0;
    }
}









