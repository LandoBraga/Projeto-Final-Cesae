# Sistema de Gestão e Manutenção de Avarias em Equipamentos

Uma aplicação desenvolvida em **Laravel** para o registo, acompanhamento e gestão de avarias em equipamentos, permitindo controlar todo o ciclo de vida de um ticket de manutenção.

## Objetivo

O objetivo deste projeto é disponibilizar uma plataforma web que facilite a comunicação entre utilizadores, técnicos e administradores, tornando o processo de gestão de avarias mais organizado, rápido e rastreável.

---

## Funcionalidades

### Utilizador

- Registo e autenticação.
- Criação de tickets de avaria.
- Associação opcional do ticket a um equipamento e/ou sala.
- Consulta do estado das avarias reportadas.

### Técnico

- Consulta de tickets abertos.
- Início da reparação de um ticket.
- Fecho da reparação com:
  - Tempo gasto;
  - Custo da intervenção.
- Pedido de aprovação de orçamento quando necessário.

### Administrador

- Aprovação de pedidos de orçamento.
- Consulta de estatísticas do sistema.

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
