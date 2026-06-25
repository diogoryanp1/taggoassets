# ADR 0023 — Catálogo e cadastro inicial de ativos

## Contexto

O patrimônio exige classificação tenant-scoped, uma numeração única e relações consistentes sem abrir caminho para IDOR ou dados de outro tenant.

## Decisão

Categorias são hierárquicas por tenant; tipos, marcas e modelos são entidades separadas. Valores globais de unidades e condições coexistem com valores do tenant. Ativos usam valor em centavos, status centralizado e sequência patrimonial protegida por transação e lock PostgreSQL.

## Alternativas

Usar uma tabela única para categoria/tipo, números gerados por `MAX + 1`, valores monetários decimais ou registros globais editáveis pelo tenant.

## Consequências

Há mais relações e validações de consistência, mas filtros, auditoria, permissions e evolução do catálogo permanecem explícitos. A listagem de ativos exige filtro mínimo.

## Riscos

Fluxos futuros de inventário e movimentação precisam respeitar os mesmos status e não podem excluir entidades em uso. A geração concorrente exige que chamadas continuem dentro de transação.
