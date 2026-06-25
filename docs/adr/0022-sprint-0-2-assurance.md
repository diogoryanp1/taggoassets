# ADR 0022 — Garantias da Sprint 0.2

| Decisão | Contexto | Alternativas | Consequências | Riscos |
|---|---|---|---|---|
| PHPStan/Larastan obrigatório | Detectar erros de tipo em Laravel | Somente testes | Análise nível 5 em `app` e `routes` | Anotações incorretas mascaram contratos |
| Análise estática no CI | Evitar regressão | Execução local opcional | Pipeline falha em erro estático | Dependência de lock atualizado |
| PostgreSQL e Redis no CI | Semântica real de produção | SQLite e array | CI usa PostgreSQL 17, Redis 7 e Predis | Serviços devem estar saudáveis |
| Binding por `public_id` | Evitar enumeração de IDs | IDs numéricos | URLs usam ULID | Não substitui policy |
| Tenancy e policies obrigatórias | Isolamento SaaS e RBAC | Filtros em views | Middleware e policies protegem rotas | Omissão em recurso novo |
| Sessões Redis revogáveis | Bloqueio e reset devem invalidar login | Remover registro somente | Handler de sessão é destruído | Driver errado invalida garantia |
| Convites com hash e after-commit | Evitar vazamento e estado parcial | Token em texto | SHA-256 e notificação enfileirada | Token só aparece no envio |
| Storage privado, MIME e SHA-256 | Proteger documentos | Disco público | Storage interno e validação estrita | Formatos novos exigem revisão |
| Arquivos sob demanda e paginação | Conter I/O e memória | Preload/listas ilimitadas | Resolver 20/100 e endpoints explícitos | N+1 em rotas novas |
| Auditoria sanitizada | Rastrear sem segredos | Payload bruto | Sanitizador recursivo e logs imutáveis | Novos campos sensíveis exigem regra |
