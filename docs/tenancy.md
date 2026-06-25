# Tenancy

Cache, convites, documentos, auditoria e consultas sao particionados por tenant. Tenant inativo, usuario bloqueado ou vinculo removido nao mantem acesso a rotas tenant-scoped.

O tenant ativo vem exclusivamente de `session.active_tenant`, gravado apos verificacao da associacao do usuario. `ResolveCurrentTenant` valida usuario, associacao e status antes de disponibilizar `CurrentTenant`.

`tenant_id` recebido por formulario, query string ou JavaScript nao define escopo. Rotas publicas usam ULID e recursos de outro tenant retornam 404.

## Catalogo e ativos

Categorias, tipos, marcas, modelos, campos customizados e ativos sao tenant-scoped. Unidades de medida e condicoes podem ser globais (`tenant_id = null`) ou do tenant. Queries usam scopes `forTenant` ou `availableToTenant`.

Relacoes enviadas por formulario sao resolvidas por ULID publico dentro do tenant atual. IDs internos numericos nao sao aceitos como entrada publica.
