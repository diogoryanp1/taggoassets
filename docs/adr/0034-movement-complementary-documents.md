# ADR 0034 - Documentos complementares vinculados às movimentações

## Contexto

Movimentações precisam de comprovantes, autorizações e termos assinados sem duplicar upload privado.

## Decisão

Criar `asset_movement_documents` como vínculo entre `AssetMovement` e `PrivateDocument`, com tipo documental e usuário.

## Alternativas

Colunas específicas por documento na movimentação ou relação polimórfica genérica.

## Consequências

O módulo existente de storage privado é preservado e a movimentação ganha histórico documental próprio.

## Riscos

Regras de preservação precisam ser mantidas para evitar exclusão indevida de evidências patrimoniais.
