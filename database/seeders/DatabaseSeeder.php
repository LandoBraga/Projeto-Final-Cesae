<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Room;
use App\Models\Equipment;
use App\Models\Ticket;
use App\Models\TicketStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // ==========================================
        // CRIAR PERFIS PADRÃO E OBTER OS IDs (RBAC)
        // ==========================================


        $adminProfileId = DB::table('user_profiles')->insertGetId([
            'name'       => 'admin',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $techProfileId = DB::table('user_profiles')->insertGetId([
            'name'       => 'technician',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $userProfileId = DB::table('user_profiles')->insertGetId([
            'name'       => 'user',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // ==========================================
        // CRIAR UTILIZADORES PADRÃO COM OS IDs
        // ==========================================

        $admin = User::factory()->create([
            'name'       => 'Administrador',
            'email'      => 'admin@example.com',
            'profile_id' => $adminProfileId,
            'password'   => Hash::make('admin123'),
            'active'     => true,
            'api_token'  => 'admin-token-' . Str::random(40),
        ]);

        $tech = User::factory()->create([
            'name'       => 'Técnico',
            'email'      => 'tech@example.com',
            'profile_id' => $techProfileId,
            'password'   => Hash::make('tech123'),
            'active'     => true,
            'api_token'  => 'tech-token-' . Str::random(40),
        ]);

        $funcionario = User::factory()->create([
            'name'       => 'Utilizador',
            'email'      => 'user@example.com',
            'profile_id' => $userProfileId,
            'password'   => Hash::make('user123'),
            'active'     => true,
            'api_token'  => 'user-token-' . Str::random(40),
        ]);

        // Executa o seeder de configurações/lookups essenciais
        $this->call([
            TicketLookupSeeder::class,
        ]);

        // ==========================================
        // MAIS IMPORTANTE: IR BUSCAR OS IDs DOS ESTADOS
        // ==========================================
        // Mapeia o nome do estado (gerado no TicketLookupSeeder) para o respetivo ID da BD
        $statusAbertaId    = TicketStatus::where('name', 'aberta')->value('id');
        $statusEmCursoId   = TicketStatus::where('name', 'em curso')->value('id');
        $statusFechadaId   = TicketStatus::where('name', 'fechada')->value('id');


        // ==========================================
        // CRIAR INFRAESTRUTURA (SALAS / LOCALIZAÇÕES)
        // ==========================================
        $salaA = Room::create([
            'name'     => 'Linha de Montagem A',
            'location' => 'Pavilhão Industrial 1',
            'active'   => true,
        ]);

        $salaID = Room::create([
            'name'     => 'Laboratório de I&D',
            'location' => 'Edifício Central - Piso 2',
            'active'   => true,
        ]);

        $armazem = Room::create([
            'name'     => 'Armazém Logístico',
            'location' => 'Pavilhão Sul',
            'active'   => true,
        ]);


        // ==========================================
        // CRIAR ATIVOS (INVENTÁRIO DE EQUIPAMENTOS)
        // ==========================================
        $kuka = Equipment::create([
            'name'     => 'Braço Robótico KUKA KR210',
            'serial'   => 'KUKA-KR210-2026',
            'room_id'  => $salaA->id,
            'active'   => true,
        ]);

        $prensa = Equipment::create([
            'name'     => 'Prensa Hidráulica 50T',
            'serial'   => 'PRES-HYD-50T-99',
            'room_id'  => $salaA->id,
            'active'   => true,
        ]);

        $servidor = Equipment::create([
            'name'     => 'Servidor Central Dell PowerEdge',
            'serial'   => 'DELL-PE-R750-SRV',
            'room_id'  => $salaID->id,
            'active'   => true,
        ]);

        $empilhador = Equipment::create([
            'name'     => 'Empilhador Elétrico Toyota',
            'serial'   => 'TOY-ELEC-404',
            'room_id'  => $armazem->id,
            'active'   => true,
        ]);


        // ==========================================
        // CRIAR TICKETS EM DIFERENTES ESTADOS (WORKFLOW)
        // ==========================================

        // Ticket 1: Estado [Aberta] - Criado pelo Funcionário, aguarda triagem
        Ticket::create([
            'user_id'      => $funcionario->id,
            'room_id'      => $salaA->id,
            'equipment_id' => $kuka->id,
            'title'        => 'Erro de comunicação no controlador do Braço Robótico',
            'description'  => 'O painel apresenta o erro intermitente "E-2038 Bus Error" e interrompe a linha de produção.',
            'status_id'    => $statusAbertaId, // Correção aqui
            'priority'     => 'alta',
            'opened_at'    => now()->subHours(4),
        ]);

        // Ticket 2: Estado [Em Curso] - Atribuído ao técnico e em reparação física
        Ticket::create([
            'user_id'        => $funcionario->id,
            'assigned_to'    => $tech->id,
            'room_id'        => $salaID->id,
            'equipment_id'   => $servidor->id,
            'title'          => 'Lentidão crítica e sobreaquecimento no nó primário',
            'description'    => 'Os discos estão com latência elevada e a ventoinha secundária parece fazer um ruído anormal.',
            'status_id'      => $statusEmCursoId, // Correção aqui
            'priority'       => 'média',
            'opened_at'      => now()->subDays(1),
            'in_progress_at' => now()->subHours(18),
        ]);

        // Ticket 3: Estado [Fechada] - Reparação concluída com sucesso (Métricas de MTTR/Custo)
        Ticket::create([
            'user_id'        => $funcionario->id,
            'assigned_to'    => $tech->id,
            'room_id'        => $salaA->id,
            'equipment_id'   => $prensa->id,
            'title'          => 'Fuga de óleo visível no pistão hidráulico principal',
            'description'    => 'Gotejamento constante na base da prensa após operação prolongada a alta pressão.',
            'status_id'      => $statusFechadaId, // Correção aqui
            'priority'       => 'alta',
            'opened_at'      => now()->subDays(4),
            'in_progress_at' => now()->subDays(4)->addHours(2),
            'closed_at'      => now()->subDays(3),
            'minutes_spent'  => 1320,
            'cost'           => 125.50,
        ]);

        // Ticket 4: Estado [Pendente de Orçamento] - Fluxo Extraordinário
        Ticket::create([
            'user_id'          => $funcionario->id,
            'assigned_to'      => $tech->id,
            'room_id'          => $armazem->id,
            'equipment_id'     => $empilhador->id,
            'title'            => 'Substituição completa do módulo de baterias de Lítio',
            'description'      => 'O empilhador não segura carga por mais de 30 minutos. Diagnóstico técnico aponta para a necessidade de um pack de células novo.',
            'status_id'        => $statusEmCursoId, // Correção aqui
            'priority'         => 'alta',
            'opened_at'        => now()->subDays(5),
            'in_progress_at'   => now()->subDays(5)->addHour(),
            'budget_requested' => true,
            'budget_status'    => 'pending',
            'budget_amount'    => 2450.00,
        ]);

        // Ticket 5: Manutenção Preventiva Agendada (Criada pelo Admin/Sistema)
        Ticket::create([
            'user_id'       => $admin->id,
            'room_id'       => $salaID->id,
            'equipment_id'  => $servidor->id,
            'title'         => 'Limpeza física interna e substituição de massa térmica (Trimestral)',
            'description'   => 'Procedimento preventivo padrão para evitar acumulação de poeiras nos dissipadores.',
            'status_id'     => $statusAbertaId, // Correção aqui
            'priority'      => 'baixa',
            'scheduled'     => true,
            'scheduled_at'  => now()->addDays(3)->setTime(9, 0),
            'scheduled_end' => now()->addDays(3)->setTime(11, 0),
        ]);
    }
}
