<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * БЕЗОПАСНОСТЬ: Удаление паролей в открытом виде из базы данных
 * 
 * Эта миграция:
 * 1. Очищает все значения admin_password_plain (устанавливает в NULL)
 * 2. НЕ удаляет колонку (для обратной совместимости)
 * 
 * После миграции пароли будут храниться ТОЛЬКО в захешированном виде.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::connection('wordpress')->hasColumn('wp_users', 'admin_password_plain')) {
            DB::connection('wordpress')->table('wp_users')
                ->whereNotNull('admin_password_plain')
                ->update(['admin_password_plain' => null]);
            
            \Log::info('Security: Cleared all plain-text passwords from wp_users table');
        }
    }

    /**
     * Reverse the migrations.
     * 
     * ВНИМАНИЕ: Откат НЕ восстановит пароли (это было бы небезопасно)
     */
    public function down(): void
    {
        // Намеренно пусто - нельзя восстановить plain пароли
        \Log::warning('Security: Cannot restore plain-text passwords on rollback');
    }
};
