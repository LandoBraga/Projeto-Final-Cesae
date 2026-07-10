<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TicketType;
use App\Models\TicketStatus;

class TicketLookupSeeder extends Seeder
{
    public function run(): void
    {
        // Criar os Tipos de Avaria Padrão
        $hardware = TicketType::create([
            'name' => 'Hardware',
            'description' => 'Problemas físicos em computadores, impressoras, periféricos ou componentes.'
        ]);

        $software = TicketType::create([
            'name' => 'Software',
            'description' => 'Problemas com o Sistema Operativo, aplicações, licenças ou lentidão de software.'
        ]);

        $rede = TicketType::create([
            'name' => 'Rede / Internet',
            'description' => 'Falhas de ligação, cablagem, Wi-Fi, routers ou acesso a servidores locais.'
        ]);

        // Criar os Estados Padrão da Avaria
        // Nota: Os nomes devem coincidir com as constantes do Model Ticket (aberta, em curso, fechada)
        TicketStatus::create([
            'name' => 'aberta',
            'description' => 'Ticket registado com sucesso e a aguardar triagem ou atribuição de técnico.',
            'type_id' => null // Estado global inicial
        ]);

        TicketStatus::create([
            'name' => 'em curso',
            'description' => 'A avaria está a ser analisada ou reparada por um técnico responsável.',
            'type_id' => null
        ]);

        TicketStatus::create([
            'name' => 'fechada',
            'description' => 'A intervenção foi concluída e o problema foi dado como resolvido.',
            'type_id' => null
        ]);

        // Exemplo de estado específico que podes associar a um tipo (opcional)
        TicketStatus::create([
            'name' => 'aguarda peças',
            'description' => 'A reparação física está suspensa até que os componentes necessários cheguem.',
            'type_id' => $hardware->id
        ]);

        TicketStatus::create([
            'name' => 'cancelada',
            'description' => 'O ticket foi cancelado pelo utilizador antes da intervenção começar.',
            'type_id' => null
        ]);

        TicketStatus::create([
            'name' => 'pendente orçamento',
            'description' => 'A reparação aguardou aprovação orçamental por parte da direção.',
            'type_id' => null
        ]);

        TicketStatus::create([
            'name' => 'recusada',
            'description' => 'O pedido de orçamento foi rejeitado e o processo não prosseguiu.',
            'type_id' => null
        ]);
    }
}
