# Taggo Assets

O catálogo patrimonial e o cadastro inicial de ativos estão documentados em `docs/catalog.md` e `docs/assets.md`.

## Qualidade

`composer analyse` executa PHPStan 2 com Larastan no nível 5 sobre `app` e `routes`. O CI executa a mesma verificação, Pint, build Vite e testes com PostgreSQL 17 e Redis via Predis.

## Redis local

O ambiente de desenvolvimento e os testes usam Redis para cache, filas e sessões. No Laragon, inicie `C:\laragon\bin\redis\redis-x64-5.0.14.1\redis-server.exe` com `redis.windows.conf` e valide com `redis-cli ping`; a resposta deve ser `PONG`. Mantenha `CACHE_STORE=redis`, `QUEUE_CONNECTION=redis`, `SESSION_DRIVER=redis`, `SESSION_STORE=redis` e `REDIS_CLIENT=predis`.

Plataforma SaaS multiempresa para gestão de ativos. Slogan: **Identifique. Localize. Controle.**

## Desenvolvimento

```powershell
copy .env.example .env
composer install
npm install
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
php artisan queue:work redis --tries=3 --timeout=120
```

O ambiente padrão usa PostgreSQL e Redis. No Laragon, inicie PostgreSQL e Redis, crie o banco com `createdb -h 127.0.0.1 -U postgres taggo_assets` e execute `php artisan migrate:fresh --seed --force`. O seeder cria contas apenas fora de produção: `superadmin@taggo.test`, `admin@taggo.test`, `manager@taggo.test` e `member@taggo.test`; senha `ChangeMe!12345`.

Executar os testes:

```powershell
php artisan test
```

## Sprint 1

A Sprint 1 cobre catalogo patrimonial, cadastro inicial de ativos, tenancy, RBAC, auditoria, ULIDs publicos, selecoes progressivas e layout administrativo responsivo. O remoto Git ainda aponta para `laravel/laravel`; nao altere sem o endereco real do repositorio Taggo Assets.

## Validacao final

Use `redis-cli ping`, `php artisan optimize:clear`, `php artisan migrate:fresh --seed --force`, `php artisan test`, `vendor/bin/pint --test`, `composer analyse` e `npm run build`. PostgreSQL e Redis precisam estar ativos localmente para a suite completa.

Consulte `docs/` para decisões, segurança, tenancy, desempenho, desenvolvimento e deploy.
