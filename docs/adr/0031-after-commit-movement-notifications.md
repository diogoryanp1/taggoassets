# ADR 0031 - Notificações após commit

## Decisão

Notificações de movimentação usam fila e `afterCommit`.

## Consequência

E-mails não são disparados para transações que falham ou são revertidas.
