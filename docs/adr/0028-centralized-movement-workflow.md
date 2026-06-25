# ADR 0028 - Workflow centralizado

## Decisão

`AssetMovementWorkflowService` concentra status inicial, transições, validação por tipo, atualização do ativo, auditoria e invalidação de cache.

## Consequência

Controllers apenas orquestram requests e policies; regras de transição não ficam espalhadas.
