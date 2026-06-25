# Alertas de retorno

Os comandos `assets:returns:upcoming` e `assets:returns:overdue` verificam saídas temporárias e empréstimos concluídos, ainda sem retorno real.

Configuração:

```env
ASSET_RETURN_REMINDER_DAYS=3
```

O scheduler executa diariamente:

- `assets:returns:upcoming` às 08:00.
- `assets:returns:overdue` às 08:10.

Cada execução marca a movimentação em `metadata.return_alerts` com o marco diário, evitando notificações duplicadas para o mesmo movimento no mesmo dia. Destinatários são deduplicados entre solicitante, responsável vinculado a usuário e gestores com permissão de movimentação no tenant.
