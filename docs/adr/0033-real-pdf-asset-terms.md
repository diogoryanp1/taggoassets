# ADR 0033 - Geração real de PDF dos termos

## Contexto

O gerador mínimo anterior produzia bytes com assinatura PDF, mas não usava biblioteca apropriada nem templates reutilizáveis.

## Decisão

Usar `barryvdh/laravel-dompdf` e templates Blade para termos patrimoniais.

## Alternativas

Manter o gerador textual mínimo ou integrar serviço externo de PDF.

## Consequências

Os termos passam a ser PDFs reais, versionados por template e armazenados em storage privado com SHA-256.

## Riscos

Dompdf tem limitações de CSS avançado; os templates devem permanecer simples e sem conteúdo remoto.
