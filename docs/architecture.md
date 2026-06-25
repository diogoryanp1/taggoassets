# Arquitetura

O Taggo Assets e um monolito modular Laravel. O codigo de dominio reside em `app/Domain/<Dominio>`; controllers coordenam requests, services concentram regras e models permanecem tenant-scoped.

Identificadores internos sao `bigint`; URLs utilizam somente `public_id` ULID. Todo recurso de dominio deve ter `tenant_id`, indices compostos iniciados por ele e policy correspondente.

Nao use `Model::find()` para recurso tenant-scoped: inicie por `forTenant(app(CurrentTenant::class)->id())` e responda 404 para pertencimento invalido.

## Sprint 1

O dominio de patrimonio fica em `App\Domain\Assets`, com models de catalogo, enum de status e services de escrita/numero patrimonial. HTTP controllers mantem orquestracao, requests validam formato e services aplicam invariantes transacionais.

Auditoria fica em `App\Domain\Audit`; `AuditLogger` centraliza entidade, tenant, usuario, request ID e payload sanitizado.
