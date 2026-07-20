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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            
            // --- Nuevos campos agregados ---
            $table->string('surname', 250)->nullable();
            $table->string('avatar', 250)->nullable();
            $table->unsignedBigInteger('role_id')->nullable();
            $table->string('phone', 25)->nullable();
            $table->string('type_document', 50)->nullable();
            $table->string('n_document', 25)->nullable();
            $table->string('gender', 1)->nullable()->default('M');
            $table->unsignedTinyInteger('type_user')->nullable()->comment('1 cliente ecommerce y 2 es usuario admin');
            $table->unsignedTinyInteger('state')->default(1)->comment('1 es activo y 2 inactivo');
            // -------------------------------

            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            
            // Crea el campo deleted_at que aparece al final
            $table->softDeletes(); 
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};