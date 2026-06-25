# Performance

Listagens grandes exigem filtro. Use paginacao no servidor em administracao, selecao explicita de colunas, eager loading controlado e nunca inclua anexos, PDFs, imagens originais, auditoria ou historico em listas.

Dashboard usa `tenant:{id}:dashboard:summary` no Redis. Metricas sao recalculadas por job. Relatorios, imports, PDFs e miniaturas sao jobs idempotentes; documentos sao carregados apenas por acao explicita.

## Sprint 1

Listagens usam paginacao centralizada e `with()` para evitar N+1. Ativos exigem filtro minimo antes de consultar e limitam pagina a no maximo 100 registros.

Selecoes progressivas limitam respostas a 100 opcoes e carregam sob demanda.
## Sprint 2 - Movimentações

Listagens de movimentações usam paginação limitada a 100, filtros por URL, índices compostos por tenant/status/tipo e eager loading. O dashboard usa contagens agregadas em cache por tenant e invalidação após movimentações.

Alertas de retorno processam movimentos abertos em chunks, evitam N+1 com eager loading e registram marcos em metadata para não repetir notificações no mesmo dia.
