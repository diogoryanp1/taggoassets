# Deploy

O CI usa PostgreSQL 17, Redis 7 e Predis, executa migração com seed, testes, Pint, PHPStan/Larastan e build Vite. Falha em qualquer etapa bloqueia a integração.

Defina variáveis de ambiente, execute `php artisan migrate --force`, `php artisan config:cache`, `php artisan route:cache` e mantenha um worker Redis supervisionado. Use HTTPS, `SESSION_SECURE_COOKIE=true`, `APP_DEBUG=false`, `LOG_LEVEL=warning` e armazenamento privado/S3 compatível.
