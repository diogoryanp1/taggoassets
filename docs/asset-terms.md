# Termos patrimoniais

Termos de responsabilidade, transferência e empréstimo são gerados somente por ação explícita com `barryvdh/laravel-dompdf` e templates Blade em `resources/views/pdfs/asset-terms`.

O arquivo é armazenado em storage privado como `PrivateDocument`, com SHA-256 calculado sobre os bytes reais, MIME `application/pdf` e vínculo à movimentação por `asset_movement_documents` como `generated_term`.

O termo contém identidade do tenant, ativo, número patrimonial, descrição, marca/modelo, número de série, origem, destino, responsável, motivo, identificador público da movimentação, data de geração e espaços de assinatura.

Downloads passam pela policy de documento privado e exigem `asset_terms.download` ou `asset_movement_documents.download` quando o documento está vinculado a uma movimentação.
