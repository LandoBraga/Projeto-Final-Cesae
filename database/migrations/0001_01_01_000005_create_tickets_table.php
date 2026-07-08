<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabela TipoAvaria do teu DER
        Schema::create('ticket_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 191)->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 2. Tabela EstadosAvaria do teu DER
        Schema::create('ticket_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name', 191)->unique();
            $table->text('description')->nullable();
            $table->foreignId('type_id')->nullable()->constrained('ticket_types')->nullOnDelete();
            $table->timestamps();
        });

        // 3. Tabela Avarias (Tickets) Unificada
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->nullable()->constrained('equipments')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('room_id')->nullable()->constrained('rooms')->nullOnDelete();
            $table->foreignId('status_id')->nullable()->constrained('ticket_statuses')->nullOnDelete();

            $table->string('title');
            $table->text('description');
            $table->string('priority')->default('média');

            // Datas de controlo de estado
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('in_progress_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('reopened_at')->nullable();

            // Bloco de Agendamento unificado (evita o ficheiro secundário _add_scheduling...)
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('scheduled_end')->nullable();
            $table->boolean('scheduled')->default(false);

            // Resolução e custos gerais
            $table->integer('minutes_spent')->nullable();
            $table->decimal('cost', 10, 2)->nullable();

            // Bloco de Orçamento unificado (evita o erro do ficheiro _add_budget_fields...)
            $table->boolean('budget_requested')->default(false);
            $table->string('budget_status')->nullable();
            $table->decimal('budget_amount', 10, 2)->nullable();
            $table->foreignId('budget_approved_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });

        // 4. Tabela HistoricoWorkflowAvaria do teu DER
        Schema::create('ticket_workflow_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->foreignId('origin_status_id')->nullable()->constrained('ticket_statuses')->nullOnDelete();
            $table->foreignId('destination_status_id')->nullable()->constrained('ticket_statuses')->nullOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('comment')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_workflow_history');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('ticket_statuses');
        Schema::dropIfExists('ticket_types');
    }
};
