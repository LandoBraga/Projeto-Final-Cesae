- [x] Revisar branch `CsrfMiddlewareTest.php` para mapear cobertura dos branches do `CsrfMiddleware`
- [x] Adicionar testes adicionais para cobrir: skip em GET, skip em JSON+X-Auth-Token, validação 419 com payload completo, skip por nome de rota além do login, e falha com X-CSRF-Token vazio
- [x] Garantir que as rotas dummy usadas no teste existem no `setUp()` antes dos requests
- [x] Rodar `phpunit`/`php artisan test` para confirmar que a suíte passa (filter: CsrfMiddlewareTest)
- [x] Rodar `php artisan test` para `TicketSearchTest`, `TicketPhotoUploadTest` e `SwaggerDocumentationTest`
- [x] Rodar `php artisan test` no geral (suite completa)
- [x] Criar `tests/Feature/TicketEdgeCasesTest.php` para cobrir mais validações/branches do `TicketController` (upload max 2048, close/reopen validation, request-budget “não necessário”, schedule start/end/technician_id)





