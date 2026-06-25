# Documentos de movimentações

Movimentações podem receber documentos complementares sem duplicar o módulo de documentos privados. O arquivo é persistido em `private_documents` e o vínculo de domínio fica em `asset_movement_documents`.

Tipos documentais:

- `generated_term`: Termo gerado pelo sistema.
- `signed_term`: Termo assinado.
- `authorization`: Autorização.
- `receipt`: Comprovante.
- `photo`: Fotografia.
- `supporting_document`: Documento complementar.

Uploads aceitos: PDF, PNG e JPEG, respeitando `PRIVATE_DOCUMENT_MAX_SIZE_KB`. SVG, HTML, executáveis e compactados não são aceitos nesta etapa.

Documentos vinculados a movimentações exigem permissões próprias: `asset_movement_documents.view`, `upload`, `download` e `deactivate`. A inativação preserva o arquivo e o histórico; termo gerado não é removido silenciosamente.
