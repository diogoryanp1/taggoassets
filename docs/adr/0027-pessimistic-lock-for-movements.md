# ADR 0027 - Lock pessimista nas movimentações

## Decisão

Criação e conclusão de movimentações bloqueiam o ativo com `lockForUpdate()`.

## Consequência

Movimentações concorrentes incompatíveis são rejeitadas antes de gerar estados divergentes.
