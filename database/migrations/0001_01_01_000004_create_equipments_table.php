<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabela CategoriasEquipamento do DER
        Schema::create('equipment_categories', function (Blueprint $table) {
            $table->id(); // CategoriaId
            $table->string('name', 191)->unique(); // NomeCategoria
            $table->boolean('active')->default(true); // Estado
            $table->timestamps();
        });

        // Tabela Equipamentos do DER
        Schema::create('equipments', function (Blueprint $table) {
            $table->id(); // EquipamentoId
            $table->string('name'); // Nome
            $table->string('serial', 191)->unique(); // NumeroSerie
            $table->foreignId('room_id')->nullable()->constrained('rooms')->nullOnDelete(); // SalaId FK
            $table->foreignId('category_id')->nullable()->constrained('equipment_categories')->nullOnDelete(); // CategoriaId FK
            $table->boolean('active')->default(true); // Estado
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipments');
        Schema::dropIfExists('equipment_categories');
    }
};
