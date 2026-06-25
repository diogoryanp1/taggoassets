# Performance

Listagens grandes exigem filtro. Use paginacao no servidor em administracao, selecao explicita de colunas, eager loading controlado e nunca inclua anexos, PDFs, imagens originais, auditoria ou historico em listas.

Dashboard usa `tenant:{id}:dashboard:summary` no Redis. Metricas sao recalculadas por job. Relatorios, imports, PDFs e miniaturas sao jobs idempotentes; documentos sao carregados apenas por acao explicita.

## Sprint 1

Listagens usam paginacao centralizada e `with()` para evitar N+1. Ativos exigem filtro minimo antes de consultar e limitam pagina a no maximo 100 registros.

Selecoes progressivas limitam respostas a 100 opcoes e carregam sob demanda.
