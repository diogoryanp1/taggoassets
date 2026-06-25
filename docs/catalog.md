# Catalogo patrimonial

O catalogo e isolado por tenant e usa ULIDs publicos nas rotas. Categorias sao hierarquicas; o backend rejeita pai externo, a propria categoria e ciclos.

Tipos pertencem a categorias. Marcas e modelos sao tenant-scoped; um modelo pode ser limitado a um tipo. Unidades de medida e condicoes podem ser globais do sistema ou especificas de um tenant. Registros globais sao somente leitura para tenants.

Campos customizados pertencem a uma categoria, tem chave unica nesse escopo e tipos controlados: `text`, `textarea`, `integer`, `decimal`, `date`, `boolean` e `select`. Valores sao validados no backend, nunca executados como codigo.

As permissoes `asset_categories.*`, `asset_types.*`, `asset_brands.*`, `asset_models.*`, `units_of_measure.*`, `asset_conditions.*` e `asset_custom_fields.*` protegem rotas e policies.

## CRUDs da Sprint 1

Tipos, marcas, modelos, unidades de medida, condicoes e campos customizados possuem listagem, filtros, detalhe, criacao, edicao, inativacao e reativacao. Nenhum recurso usa exclusao fisica no fluxo administrativo.

Registros globais de unidades e condicoes usam `tenant_id = null`, `is_system = true` e sao somente leitura. Registros personalizados recebem `tenant_id` no backend.

Campos `select` exigem opcoes em JSON. Regras de validacao usam whitelist e alteracao incompativel de tipo e bloqueada quando ja existem valores.
