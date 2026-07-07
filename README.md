# Sistema de Gestão e Manutenção de Avarias em Equipamentos

Uma aplicação desenvolvida em **Laravel** para o registo, acompanhamento e gestão de avarias em equipamentos, permitindo controlar todo o ciclo de vida de um ticket de manutenção.

## Objetivo

O objetivo deste projeto é disponibilizar uma plataforma web que facilite a comunicação entre utilizadores, técnicos e administradores, tornando o processo de gestão de avarias mais organizado, rápido e rastreável.

---

## Matriz de Autorizações

### Utilizador Comum (Operário/Funcionário)

- Alterar Password: Gestão autónoma da sua segurança de acesso.

- Abrir Ticket (Manutenção Corretiva): Reportar uma avaria normal de forma cirúrgica, escolhendo a sala e o equipamento.

- Consultar os Seus Tickets: Listagem exclusiva das avarias reportadas pelo próprio, para acompanhar o estado (Aberta, Em Curso, Fechada).

### Técnico de Manutenção

- Alterar Password: Gestão autónoma da sua segurança de acesso.

- Consultar Painel de Avarias Ativas: Visualizar todos os tickets em estado "Aberto" ou de cariz "Preventivo" pendentes na fábrica.

- Consultar Histórico de Ativos: Consultar a ficha dos equipamentos para perceber o histórico de avarias passadas daquela máquina.

- Iniciar Reparação: Assumir o ticket. O sistema muda o estado para "Em Curso", injeta o timestamp automático e vincula o ID do técnico ao ticket.

- Pedir Autorização Orçamental (Fluxo Excecional): Caso detete que a reparação exige peças de valor elevado, move o ticket para "Pendente de Orçamento", parando o cronómetro e justificando o valor.

- Encerrar Ticket (Custo Baixo/Autorizado): Mudar o estado para "Fechada", sendo obrigado a introduzir o tempo gasto (horas) e os comentários técnicos da resolução.

### Administrador (Diretor de Operações)

- Gestão Total de Utilizadores: Criar utilizadores, atribuir Perfis (Roles) e inativar contas (bloquear acesso).

- Gestão Total de Inventário (Ativos): Criar, atualizar e inativar Equipamentos e Categorias.

- Gestão Total de Infraestrutura: Criar, atualizar e inativar Salas/Localizações.

- Agendar Manutenções Preventivas: Gerar ordens de trabalho proativas do tipo "Preventiva" diretamente para a agenda dos técnicos.

- Aprovar Orçamentos: Analisar os pedidos de alto valor feitos pelos técnicos, clicar em "Aprovar" para reativar o ticket para o estado "Em Curso".

- Consultar Dashboard Analítico: Acesso exclusivo aos relatórios estatísticos calculados em LINQ (Tempos médios de espera, eficiência de técnicos e custos globais).

---

## Estados do Ticket

Cada ticket percorre um conjunto de estados durante o seu ciclo de vida:

- **Aberta**
- **Em curso**
- **Fechada**

São igualmente registados os seguintes momentos:

- `opened_at`
- `in_progress_at`
- `closed_at`

---

## Estatísticas

O sistema disponibiliza indicadores através do endpoint `/analytics`, incluindo:

- Tempo médio de resolução;
- Tempo médio de espera;
- Indicadores de desempenho da manutenção.

---

## Tecnologias Utilizadas

- Laravel
- PHP
- Composer
- MySQL (ou outro SGBD compatível)
- PHPUnit
- NPM

---

# Instalação

## 1. Clonar o repositório

```bash
git clone https://github.com/seu-utilizador/seu-repositorio.git
cd seu-repositorio
```

## 2. Instalar dependências

```bash
composer install
npm install
```

## 3. Configurar o ambiente

Copiar o ficheiro de configuração:

```bash
cp .env.example .env
```

Gerar a chave da aplicação:

```bash
php artisan key:generate
```

Configurar a ligação à base de dados no ficheiro `.env`.

## 4. Executar as migrations

```bash
php artisan migrate
```

Caso existam seeders:

```bash
php artisan db:seed
```

## 5. Iniciar a aplicação

```bash
php artisan serve
```

---

# Executar Testes

```bash
php artisan test
```

---

# API Endpoints

## Autenticação

| Método | Endpoint | Descrição |
|---------|----------|-----------|
| POST | `/register` | Registar utilizador |
| POST | `/login` | Iniciar sessão |

---

## Tickets

| Método | Endpoint | Permissão |
|---------|----------|-----------|
| POST | `/tickets` | Utilizador |
| GET | `/technician/tickets/open` | Técnico / Administrador |
| PUT | `/technician/tickets/{id}/start` | Técnico |
| PUT | `/technician/tickets/{id}/close` | Técnico |
| PUT | `/technician/tickets/{id}/request-budget` | Técnico |

---

## Administração

| Método | Endpoint | Permissão |
|---------|----------|-----------|
| PATCH | `/admin/tickets/{id}/approve-budget` | Administrador |
| GET | `/analytics` | Técnico / Administrador |

---

# Arquitetura de Dados - DER

<p align="center">
  <img src="docs/Diagrama de Entidade-Relacionamento (DER).drawio.svg" alt="Diagrama de Entidade-Relacionamento" width="850">
</p>


# Estrutura Geral do Fluxo

```text
Utilizador
      │
      ▼
Criar Ticket
      │
      ▼
Estado: Aberta
      │
      ▼
Técnico inicia reparação
      │
      ▼
Estado: Em curso
      │
      ├──────────────► Pedido de orçamento (opcional)
      │                       │
      │                       ▼
      │              Administrador aprova
      │
      ▼
Técnico conclui reparação
      │
      ▼
Estado: Fechada
```

---

# Melhorias Futuras

- Notificações por email.
- Notificações em tempo real.
- Histórico completo de alterações (Audit Log).
- Dashboard com gráficos e métricas.
- Upload de fotografias das avarias.
- Sistema de comentários entre utilizador e técnico.
- Pesquisa e filtros avançados.
- Gestão de equipamentos e salas.
- Gestão de manutenção preventiva.
- Exportação de relatórios (PDF/Excel).
- API documentada com Swagger/OpenAPI.

---

# Licença

Este projeto encontra-se licenciado sob a **MIT License**.

Consulte o ficheiro **LICENSE** para mais informações.
