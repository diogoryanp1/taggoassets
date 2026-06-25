# Seguranca

RBAC e validado nas policies e nas rotas diretas. Tenancy ignora `tenant_id` enviado por formulario, query ou JSON; bindings usam ULID `public_id` e recursos fora do tenant retornam 404 seguro.

CSRF e provido pelo grupo web. Cookies usam HttpOnly, criptografia de sessao, SameSite Lax e Secure em HTTPS. `SecurityHeaders` entrega CSP, HSTS em producao, anti-clickjacking, `nosniff`, Referrer e Permissions Policy.

Login e limitado por e-mail + IP. Auditoria remove senha, token, segredo, cookie e MFA. Arquivos privados ficam em `storage/app/private` e devem ser expostos apenas por controller autorizado.

## Sprint 1

As rotas administrativas usam policies, tenant atual resolvido em middleware e binding por `public_id`. Controllers abortam com 404 quando o recurso nao pertence ao tenant ou nao esta disponivel ao tenant.

Mass assignment de `tenant_id`, `public_id`, `created_by` e `updated_by` nao e aceito por requests. Esses campos sao derivados no backend.

Endpoints progressivos sao autenticados, passam por policy e filtram por tenant antes de retornar opcoes.
## Sprint 2 - Movimentações

Movimentações bloqueiam campos de origem, solicitante, aprovador e status vindos do frontend. Relações de destino são resolvidas por ULID público e validadas no tenant atual. Documentos de termos usam storage privado, SHA-256 e rota protegida por policy.

Documentos complementares aceitam apenas PDF, PNG e JPEG, não expõem path/hash interno e usam permissões específicas de movimentação. Vínculos inativados deixam de ser baixáveis.
