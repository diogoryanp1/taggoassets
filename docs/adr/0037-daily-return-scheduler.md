# ADR 0037 - Scheduler diário para vencimentos

## Contexto

Retornos próximos e vencidos precisam de verificação recorrente, mas não exigem execução por minuto.

## Decisão

Registrar `assets:returns:upcoming` às 08:00 e `assets:returns:overdue` às 08:10 com `withoutOverlapping()`.

## Alternativas

Executar por minuto ou enviar alertas apenas em acessos ao dashboard.

## Consequências

O processamento é previsível, barato e compatível com Redis/queue.

## Riscos

Ambientes de produção precisam configurar o cron do Laravel Scheduler.
