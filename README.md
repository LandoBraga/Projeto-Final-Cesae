# Sistema de Gestão e Manutenção de Avarias em Equipamentos

Uma aplicação desenvolvida em **Laravel** para o registo, acompanhamento e gestão de avarias em equipamentos, permitindo controlar todo o ciclo de vida de um ticket de manutenção.

## Objetivo

O objetivo deste projeto é disponibilizar uma plataforma web que facilite a comunicação entre utilizadores, técnicos e administradores, tornando o processo de gestão de avarias mais organizado, rápido e rastreável.

---

## Product Backlog Inicial

| Prioridade | User Story (Funcionalidade) | Critérios de Aceitação / DoD Técnico |
| :--- | :--- | :--- |
| 🔴 **Crítica** | **Como:** Funcionário<br>**Quero:** reportar uma avaria num equipamento específico<br>**Para:** que a equipa de manutenção saiba o que reparar. | • Formulário de submissão com campos: Equipamento, Sala e Descrição.<br>• Associação automática do ID do utilizador autenticado (`Auth::id()`).<br>• Estado inicial automático como 'Aberta' com carimbo `created_at`. |
| 🔴 **Crítica** | **Como:** Técnico<br>**Quero:** alterar o estado de uma avaria ("Em Curso" / "Fechada")<br>**Para:** comunicar o progresso atualizado dos trabalhos. | • Dropdown exclusivo de transição de estados no workflow.<br>• Bloqueio de rotas via Middleware do Laravel para perfis não autorizados (403).<br>• Estado 'Fechada' exige parecer técnico e injeta o timestamp final. |
| 🔴 **Crítica** | **Como:** Administrador<br>**Quero:** gerir o inventário de equipamentos e salas (CRUD)<br>**Para:** manter a infraestrutura física da empresa atualizada. | • CRUD completo integrado com *Soft Deletes* do Eloquent ORM.<br>• Validação no *Form Request* do Laravel impedindo números de série duplicados. |
| 🟡 **Alta** | **Como:** Administrador<br>**Quero:** consultar dashboards com métricas operacionais<br>**Para:** avaliar a eficiência e tempos de resolução (MTTR). | • Painel dinâmico no Front-End alimentado por assets compilados via NPM (Vite).<br>• Consultas SQL otimizadas com *Eager Loading* (`with()`) no Eloquent para evitar o problema N+1. |
| 🟡 **Alta** | **Como:** Sistema (Automação)<br>**Quero:** monitorizar a telemetria simulada dos ativos<br>**Para:** abrir avarias preventivas automaticamente em caso de anomalia. | • Execução de rotinas em segundo plano utilizando o *Laravel Task Scheduling*.<br>• Geração autónoma de ticket na base de dados caso os limites tolerados sejam violados. |

## 🔐 Matriz de Autorizações & Permissões (RBAC)

### 1. Utilizador Comum (Operário/Funcionário)
- **Alterar Password:** Gestão autónoma da sua segurança de acesso à plataforma.
- **Consultar Catálogo de Ativos (Apenas Leitura):** Capacidade de listar salas e equipamentos ativos para poder popular corretamente a interface de reporte.
- **Abrir Ticket (Manutenção Corretiva):** Reportar uma avaria real de forma cirúrgica, associando obrigatoriamente uma sala, um equipamento e uma descrição textual.
- **Consultar os Seus Tickets:** Listagem restrita e exclusiva das avarias reportadas pelo próprio utilizador, para acompanhamento em tempo real do estado (Aberta, Em Curso, Fechada).
- **Cancelar Ticket (Condicional):** Capacidade de anular o próprio alerta, desde que este ainda se encontre no estado inicial "Aberto".

### 2. Técnico de Manutenção
- **Alterar Password:** Gestão autónoma da sua segurança de acesso.
- **Consultar Painel de Avarias Ativas:** Visualizar a listagem global de todos os tickets em estado "Aberto" ou de cariz "Preventivo" agendados para a fábrica.
- **Consultar Histórico de Ativos:** Acesso à ficha técnica e ao registo histórico de intervenções passadas de qualquer máquina para apoio ao diagnóstico.
- **Iniciar Reparação:** Assumir a responsabilidade de um ticket. O sistema altera o estado para "Em Curso", injeta o timestamp automático de início e vincula o ID do técnico ao registo.
- **Pedir Autorização Orçamental (Fluxo Excecional):** Caso identifique a necessidade de peças dispendiosas, move o ticket para "Pendente de Orçamento", suspendendo o cronómetro de SLA e anexando uma justificação financeira.
- **Encerrar Ticket (Custo Baixo/Autorizado):** Submeter o encerramento da avaria (estado "Fechada"), com preenchimento obrigatório das horas de mão-de-obra investidas e do relatório técnico final.

### 3. Administrador (Diretor de Operações)
- **Gestão Total de Utilizadores:** Controlo absoluto (CRUD) sobre as contas dos colaboradores, atribuição de Perfis (Roles) e bloqueio de acessos através de inativação.
- **Gestão Total de Inventário (Ativos):** Operações estruturais (CRUD) sobre Equipamentos e Categorias, utilizando inativação lógica (*Soft Deletes* do Eloquent).
- **Gestão Total de Infraestrutura:** Criação e configuração (CRUD) de Salas e Localizações físicas.
- **Agendar Manutenções Preventivas:** Gerar ordens de trabalho proativas e planeadas cronologicamente, injetando os alertas diretamente no painel dos técnicos.
- **Aprovar/Rejeitar Orçamentos:** Decidir sobre os pedidos de alto valor submetidos pelos técnicos. A aprovação reativa o ticket para "Em Curso"; a rejeição encerra o processo ou devolve-o para revisão técnica.
- **Consultar Dashboard Analítico:** Acesso exclusivo aos relatórios estatísticos calculados via **Eloquent ORM** (Tempos médios de resolução - MTTR, eficiência da equipa técnica e custos gerais de manutenção).

---

## 🔄 Workflow & Regras de Transição de Estados

O ciclo de vida de uma avaria no sistema segue regras estritas de transição geridas via Eloquent ORM. Cada alteração de estado é auditada automaticamente pelo trait `Auditable.php`.

### 📋 Detalhe das Transições e Comportamento Esperado

#### 1. De [Aberta] para [Em Curso]
* **Gatilho:** O Técnico clica em "Iniciar Reparação" no seu painel.
* **Regra de Negócio:** - O sistema associa automaticamente o ID do técnico ao campo `tecnico_atribuido_id`.
  - É registado o timestamp exato de início de intervenção para cálculo do SLA de atendimento.
  - O ticket fica bloqueado para edição por outros técnicos.

#### 2. De [Em Curso] para [Pendente de Orçamento] (Fluxo Excecional)
* **Gatilho:** O Técnico deteta a necessidade de peças ou serviços de alto custo e clica em "Solicitar Aprovação".
* **Regra de Negócio:**
  - O preenchimento da justificativa financeira e a estimativa de custo tornam-se campos obrigatórios.
  - O cronómetro de tempo de resolução (SLA) é **suspenso** para não penalizar as métricas do técnico.
  - O Administrador é notificado no seu painel analítico.

#### 3. De [Pendente de Orçamento] para [Em Curso] ou [Recusada]
* **Gatilho:** O Administrador toma uma ação sobre o orçamento pendente.
* **Regra de Negócio:**
  - **Se Aprovado:** O estado regressa a "Em Curso", o cronómetro de SLA é reativado e o técnico pode prosseguir com a reparação.
  - **Se Rejeitado:** O estado passa para "Recusada/Cancelada", exigindo uma nota de feedback do Administrador, e o ticket é encerrado.

#### 4. De [Em Curso] para [Fechada]
* **Gatilho:** O Técnico conclui a reparação física e clica em "Encerrar Ticket".
* **Regra de Negócio:**
  - Torna-se obrigatória a introdução das horas de mão-de-obra gastas e do relatório técnico final.
  - O sistema injeta automaticamente o timestamp de conclusão (`closed_at`) e calcula o tempo total de resolução (MTTR).

#### 5. De [Aberta] para [Cancelada]
* **Gatilho:** O Funcionário que abriu o ticket decide anulá-lo por erro ou duplicação.
* **Regra de Negócio:**
  - **Condição Estrita:** Esta ação só é permitida se o ticket ainda estiver no estado inicial "Aberto". Se um técnico já tiver iniciado a reparação, o utilizador comum deixa de ter permissão para cancelar.

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
