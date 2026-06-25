# UI design system

O design system da Sprint 1 usa Blade, Tailwind CSS 4 e Alpine.js. Tokens ficam em `resources/css/app.css` e priorizam uma interface administrativa densa, legivel e responsiva.

## Tokens

- Fontes: `Inter` para texto e `Sora` para titulos.
- Cores: `primary`, `secondary`, `accent`, `success`, `warning`, `danger`, `info`, `surface`, `surface-muted`, `border`, `text-primary` e `text-secondary`.
- Raio: `rounded-control` para controles.
- Foco: `:focus-visible` com contorno primario.

## Componentes

Componentes consolidados nesta sprint: `x-layouts.app`, `x-breadcrumb`, `x-card`, `x-stat-card`, `x-button`, `x-icon-button`, `x-input`, `x-select`, `x-textarea`, `x-checkbox`, `x-toggle`, `x-field-error`, `x-form-section`, `x-filter-panel`, `x-table`, `x-pagination`, `x-badge`, `x-alert`, `x-modal`, `x-confirm-dialog`, `x-dropdown`, `x-tooltip`, `x-loading`, `x-skeleton`, `x-empty-state`, `x-drawer` e `x-icon`.

## Layout

O layout possui sidebar desktop com grupos por permissao, estado ativo de rota, preferencia de recolhimento em `localStorage` e tooltip via `title` quando recolhida. Em mobile, o drawer usa overlay, fechamento por botao, clique fora, ESC e fechamento ao navegar.

## Formularios e filtros

Forms usam labels, obrigatoriedade visivel, erros proximos do campo, preservacao de valores por `old()` e secoes semanticas. Listagens usam filtros GET, preservam query string na paginacao e exibem estado vazio separado de sem resultados.

## Selecao progressiva

Alpine.js consome endpoints autenticados para categoria/tipo, marca/modelo, unidade/localizacao e categoria/campos. O frontend exibe loading, vazio, erro e tentativa novamente, com protecao contra resposta fora de ordem. Autorizacao e integridade continuam no backend.

## Responsividade e acessibilidade

As telas foram estruturadas para 360px, 768px, 1024px e 1440px com grids responsivos, foco visivel, labels, `aria-label` em comandos iconicos e estados textuais que nao dependem apenas de cor.
## Sprint 2 - Telas operacionais

As telas de responsáveis e movimentações reutilizam cards, tabelas, filtros, badges e formulários existentes. A criação de movimentação usa seções numeradas em vez de wizard complexo, com resumo lateral e origem derivada pelo backend.
