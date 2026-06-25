# Ativos

O cadastro inicial exige categoria, tipo, unidade de medida, condicao e unidade administrativa do tenant. Marca, modelo, localizacao, numero de serie e valores customizados sao validados contra as regras do tipo e os relacionamentos do mesmo tenant.

`asset_number` e unico por tenant. Quando o usuario nao tem `assets.set_manual_number`, o backend usa `AssetNumberGenerator`, que reserva a sequencia por tenant e ano dentro de transacao com `lockForUpdate`.

Valores monetarios sao persistidos em centavos inteiros. Status sao centralizados em `AssetStatus`; esta sprint nao implementa movimentacao, inventario, manutencao, depreciacao operacional ou baixa.

## Filtros

A listagem aceita numero patrimonial, numero legado, descricao, categoria, tipo, marca, modelo, unidade administrativa, status, condicao, numero de serie, intervalo de data de aquisicao e situacao ativo/inativo. Sem filtro minimo, a tela mostra a mensagem inicial e nao executa consulta ampla.

## Formulario

O formulario usa selecoes progressivas autenticadas para categoria/tipo, marca/modelo, unidade/localizacao e categoria/campos customizados. O backend valida tenant, relacoes, status permitido, numero manual, unidade vinculada e campos customizados.

## Detalhe

A pagina de detalhe mostra identificacao, classificacao, localizacao, aquisicao, serie, valores customizados, observacoes, criador, datas principais e auditoria resumida. Documentos ficam acessiveis por acao, sem carregamento automatico.
