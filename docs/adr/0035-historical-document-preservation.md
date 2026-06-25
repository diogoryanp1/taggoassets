# ADR 0035 - Preservação de documentos históricos

## Contexto

Documentos anexados podem compor evidência patrimonial.

## Decisão

Adotar inativação do vínculo em vez de exclusão física irrestrita. Termos gerados não podem ser removidos silenciosamente.

## Alternativas

Excluir arquivo e banco imediatamente ou bloquear toda alteração.

## Consequências

O histórico permanece auditável e documentos inativados deixam de ser baixáveis pelo fluxo de movimentação.

## Riscos

Arquivos inativados continuam ocupando storage até política futura de retenção.
