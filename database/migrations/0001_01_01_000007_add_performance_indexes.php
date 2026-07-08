<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Índices para a tabela users (pesquisas freqüentes por email e profile_id)
        Schema::table('users', function (Blueprint $table) {
            $table->index('email');
            $table->index('profile_id');
            $table->index('active');
            $table->index(['profile_id', 'active']);
        });

        // Índices para a tabela user_profiles (pesquisa por name)
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->index('name');
        });

        // Índices para a tabela rooms (pesquisa por name e active)
        Schema::table('rooms', function (Blueprint $table) {
            $table->index('name');
            $table->index('active');
        });

        // Índices para a tabela equipments (pesquisa por serial, room_id, active)
        Schema::table('equipments', function (Blueprint $table) {
            $table->index('serial');
            $table->index('room_id');
            $table->index('active');
            $table->index(['room_id', 'active']);
        });

        // Índices para a tabela tickets (pesquisa freqüente por status_id, user_id, assigned_to, priority, dates)
        Schema::table('tickets', function (Blueprint $table) {
            $table->index('status_id');
            $table->index('user_id');
            $table->index('assigned_to');
            $table->index('room_id');
            $table->index('equipment_id');
            $table->index('priority');
            $table->index('opened_at');
            $table->index('in_progress_at');
            $table->index('closed_at');
            $table->index('scheduled');
            $table->index('scheduled_at');
            $table->index('budget_requested');
            $table->index('budget_status');
            
            // Índices compostos para queries freqüentes
            $table->index(['status_id', 'priority']);
            $table->index(['user_id', 'status_id']);
            $table->index(['assigned_to', 'status_id']);
            $table->index(['status_id', 'opened_at']);
        });

        // Índices para a tabela ticket_statuses (pesquisa por name)
        Schema::table('ticket_statuses', function (Blueprint $table) {
            $table->index('name');
            $table->index('type_id');
        });

        // Índices para a tabela ticket_types (pesquisa por name)
        Schema::table('ticket_types', function (Blueprint $table) {
            $table->index('name');
        });
    }

    public function down(): void
    {
        // Remover índices na ordem inversa da criação
        Schema::table('ticket_types', function (Blueprint $table) {
            $table->dropIndex('name');
        });

        Schema::table('ticket_statuses', function (Blueprint $table) {
            $table->dropIndex('type_id');
            $table->dropIndex('name');
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex(['status_id', 'opened_at']);
            $table->dropIndex(['assigned_to', 'status_id']);
            $table->dropIndex(['user_id', 'status_id']);
            $table->dropIndex(['status_id', 'priority']);
            $table->dropIndex('budget_status');
            $table->dropIndex('budget_requested');
            $table->dropIndex('scheduled_at');
            $table->dropIndex('scheduled');
            $table->dropIndex('closed_at');
            $table->dropIndex('in_progress_at');
            $table->dropIndex('opened_at');
            $table->dropIndex('priority');
            $table->dropIndex('equipment_id');
            $table->dropIndex('room_id');
            $table->dropIndex('assigned_to');
            $table->dropIndex('user_id');
            $table->dropIndex('status_id');
        });

        Schema::table('equipments', function (Blueprint $table) {
            $table->dropIndex(['room_id', 'active']);
            $table->dropIndex('active');
            $table->dropIndex('room_id');
            $table->dropIndex('serial');
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->dropIndex('active');
            $table->dropIndex('name');
        });

        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropIndex('name');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['profile_id', 'active']);
            $table->dropIndex('active');
            $table->dropIndex('profile_id');
            $table->dropIndex('email');
        });
    }
};