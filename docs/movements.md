# Movimentações patrimoniais

Movimentações são registros de domínio imutáveis para alterações de unidade, localização, responsável, saída temporária e empréstimo. A origem é sempre derivada do estado atual do ativo no backend.

Tipos operacionais: atribuição inicial, transferência interna, alteração de localização, alteração de responsável, saída temporária, retorno temporário, empréstimo e retorno de empréstimo. Tipos de manutenção ficam catalogados para evolução futura.

Status: rascunho, aguardando aprovação, aprovada, rejeitada, concluída e cancelada. O frontend não define transições diretamente; `AssetMovementWorkflowService` centraliza criação, aprovação, rejeição, cancelamento e conclusão.

Transferências entre unidades e empréstimos entram como aguardando aprovação. Mudanças internas permitidas podem ser concluídas diretamente. A conclusão atualiza o ativo em transação com `lockForUpdate()`.

Filtros principais: número patrimonial, tipo, status, intervalo de datas e retornos atrasados. A listagem é paginada, limitada a 100 itens por página e usa eager loading.

Documentos complementares são anexados a partir dos detalhes da movimentação. A tela lista tipo documental, nome, data, usuário, tamanho e ações autorizadas, sem exibir caminho de storage, ID interno ou hash completo.
