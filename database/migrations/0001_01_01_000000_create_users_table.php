<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabela PerfisUtilizador do DER
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id(); // PerfilId
            $table->string('name', 191)->unique(); // NomePerfil
            $table->timestamps();
        });

        // Tabela Utilizadores do DER
        Schema::create('users', function (Blueprint $table) {
            $table->id(); // UtilizadorId
            $table->string('name'); // NomeCompleto
            $table->string('email', 191)->unique(); // Email (Protegido com 191)
            $table->foreignId('profile_id')->nullable()->constrained('user_profiles')->nullOnDelete(); // PerfilId FK
            $table->boolean('active')->default(true); // Estado (bit)
            $table->string('api_token', 80)->unique()->nullable()->default(null);
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email', 191)->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id', 191)->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('user_profiles');
    }
};
