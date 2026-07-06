<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')       ->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_to')   ->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('equipment_id')  ->nullable()->constrained('equipments')->nullOnDelete();
            $table->foreignId('room_id')       ->nullable()->constrained('rooms')->nullOnDelete();
            $table->string   ('title');
            $table->text     ('description');
            $table->string   ('status')        ->default('aberta');
            $table->timestamp('opened_at')     ->nullable();
            $table->timestamp('in_progress_at')->nullable();
            $table->timestamp('closed_at')     ->nullable();
            $table->integer  ('minutes_spent') ->nullable();
            $table->decimal  ('cost', 10, 2)   ->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
