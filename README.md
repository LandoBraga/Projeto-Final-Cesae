## Projeto: Gestão e Manutenção de Avarias em Equipamentos

Este repositório contém uma aplicação Laravel simples para gerir avarias em equipamentos.

- Objetivo: Plataforma web para registo, acompanhamento e resolução de avarias em equipamentos.
- Papéis de utilizador: utilizador comum, técnico e administrador.

Funcionalidades principais:

- Um utilizador registado pode reportar uma avaria criando um `ticket` com título, descrição, equipamento (opcional) e sala (opcional).
- Um painel de tickets abertos está disponível para técnicos (`GET /technician/tickets/open`).
- O técnico pode iniciar a reparação (`PUT /technician/tickets/{id}/start`) e, quando concluída, fechar o ticket (`PUT /technician/tickets/{id}/close`) informando tempo gasto e custo.
- Se o custo ultrapassar um limiar, o técnico pode pedir autorização de orçamento (`PUT /technician/tickets/{id}/request-budget`).
- O administrador pode aprovar orçamentos pendentes (`PATCH /admin/tickets/{id}/approve-budget`).
- Os estados de um ticket são: `aberta`, `em curso`, `fechada`.
- São registadas as horas de `opened_at`, `in_progress_at` e `closed_at`.
- Dados estatísticos disponíveis via `GET /analytics` (média de tempo de resolução e tempo de espera).

Como executar (local):

1. Instale dependências via Composer e NPM conforme o seu ambiente.
2. Configure o ficheiro `.env` e crie uma base de dados local.
3. Execute migrations:

```bash
php artisan migrate
```

4. Execute testes:

```bash
php artisan test
```

Endpoints rápidos (resumo):

- `POST /register` — registar utilizador
- `POST /login` — iniciar sessão (gera `api_token`)
- `POST /tickets` — criar ticket (utilizador comum)
- `GET /technician/tickets/open` — ver tickets abertos (técnico/ADM)
- `PUT /technician/tickets/{id}/start` — iniciar reparação (técnico)
- `PUT /technician/tickets/{id}/close` — fechar e registar custo/tempo (técnico)
- `PUT /technician/tickets/{id}/request-budget` — pedir autorização de orçamento (técnico)
- `PATCH /admin/tickets/{id}/approve-budget` — aprovar orçamento (ADM)
- `GET /analytics` — estatísticas (ADM/Técnico)

Sugestões futuras:

- Notificações por email quando um orçamento é pedido/ aprovado.
- Histórico detalhado de alterações em cada ticket (audit log).
- Interface web com filtros por estado, equipamento, sala e técnico.
