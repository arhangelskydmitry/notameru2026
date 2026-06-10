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
        Schema::connection('wordpress')->table('wp_users', function (Blueprint $table) {
            if (!Schema::connection('wordpress')->hasColumn('wp_users', 'admin_password')) {
                $table->string('admin_password', 255)->nullable()->after('user_pass');
            }
            if (!Schema::connection('wordpress')->hasColumn('wp_users', 'admin_password_plain')) {
                $table->string('admin_password_plain', 255)->nullable()->after('user_pass');
            }
            if (!Schema::connection('wordpress')->hasColumn('wp_users', 'admin_account_active')) {
                $table->boolean('admin_account_active')->default(true)->after('admin_password_plain');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('wordpress')->table('wp_users', function (Blueprint $table) {
            $columns = [];
            if (Schema::connection('wordpress')->hasColumn('wp_users', 'admin_password')) {
                $columns[] = 'admin_password';
            }
            if (Schema::connection('wordpress')->hasColumn('wp_users', 'admin_password_plain')) {
                $columns[] = 'admin_password_plain';
            }
            if (Schema::connection('wordpress')->hasColumn('wp_users', 'admin_account_active')) {
                $columns[] = 'admin_account_active';
            }
            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
};
