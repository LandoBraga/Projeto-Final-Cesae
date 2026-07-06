<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Adiciona colunas para gerir pedidos e aprovações de orçamento
        Schema::table('tickets', function (Blueprint $table) {
            // Indica se um técnico pediu autorização de orçamento
            $table->boolean('budget_requested')->default(false)->after('cost');

            // Estado do pedido de orçamento (pending/approved/rejected)
            $table->string('budget_status')->nullable()->after('budget_requested'); // pending, approved, rejected

            // Valor do orçamento solicitado (usualmente igual a `cost` quando pedido)
            $table->decimal('budget_amount', 10, 2)->nullable()->after('budget_status');

            // Referência ao utilizador (ADM) que aprovou o orçamento
            $table->foreignId('budget_approved_by')->nullable()->constrained('users')->nullOnDelete()->after('budget_amount');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Remove a foreign key e as colunas adicionadas no up()
            $table->dropForeign(['budget_approved_by']);
            $table->dropColumn(['budget_requested', 'budget_status', 'budget_amount', 'budget_approved_by']);
        });
    }
};
