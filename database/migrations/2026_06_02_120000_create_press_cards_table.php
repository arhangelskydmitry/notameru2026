<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql')->create('press_cards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('card_number', 32)->unique();
            $table->string('full_name');
            $table->string('position')->nullable();
            $table->string('organization')->default('Интернет-издание «Нота Миру»');
            $table->string('photo_path')->nullable();
            $table->date('issued_at');
            $table->date('expires_at');
            $table->enum('status', ['active', 'revoked', 'expired'])->default('active');
            $table->unsignedBigInteger('issued_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('mysql')->dropIfExists('press_cards');
    }
};
