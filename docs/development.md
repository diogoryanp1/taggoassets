# Desenvolvimento

PHP 8.3+, Laravel 12, PostgreSQL, Redis, Blade, Tailwind, Alpine/Vite. Use Form Requests, `$fillable` explicito, policies e transacoes para operacoes criticas. Rode `vendor/bin/pint` antes de enviar alteracoes.

Ambientes: local, testing, staging e production. Producao exige `APP_DEBUG=false`, credenciais externas ao repositorio e bancos separados de staging.

Para validar infraestrutura local no Windows/Laragon: inicie PostgreSQL e Redis, configure `.env` com `DB_CONNECTION=pgsql`, `CACHE_STORE=redis`, `QUEUE_CONNECTION=redis` e `SESSION_DRIVER=redis`, entao execute `php artisan optimize:clear` e `php artisan migrate:fresh --seed --force`.

## Padrao Sprint 1

Ao adicionar recursos tenant-scoped, siga o padrao: model com `public_id`, scope de tenant e factory; request aceitando apenas campos publicos; controller resolvendo tenant e relacoes no backend; policy por permissao; auditoria com payload sanitizado; testes de CRUD, RBAC, tenancy, IDOR e binding.

Nao use seeders como pre-condicao de factories.
