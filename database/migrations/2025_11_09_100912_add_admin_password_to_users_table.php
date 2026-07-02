<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('wp_users', function (Blueprint $table) {
            // Добавляем поле для Laravel-пароля (независимое от WordPress)
            $table->string('admin_password', 255)->nullable()->after('user_pass');
            // Добавляем поле для хранения незашифрованного пароля (только для суперадмина)
            $table->string('admin_password_plain', 255)->nullable()->after('admin_password');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wp_users', function (Blueprint $table) {
            $table->dropColumn(['admin_password', 'admin_password_plain']);
        });
    }
};
