# ADR 0036 - Alertas de retorno idempotentes

## Contexto

Alertas de vencimento podem ser executados diariamente e não devem repetir e-mails para o mesmo marco.

## Decisão

Registrar marcos em `asset_movements.metadata.return_alerts`.

## Alternativas

Criar tabela dedicada de notificações ou depender apenas de logs de auditoria.

## Consequências

O controle fica próximo ao movimento e evita duplicidade diária.

## Riscos

Consultas futuras de analytics podem exigir normalização em tabela própria.
