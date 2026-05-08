# DECISIONS

## Refatoração Arquitetural de Serviços e Interfaces

**Data:** 2026-05-06
**Proponente:** Usuário
**Revisor:** @ARCH

### Contexto
A estrutura atual de `App\Infrastructure\Services` mistura interfaces de contrato (Domínio) com implementações concretas (Infraestrutura), causando acoplamento e confusão de responsabilidades.

### Decisão
1.  **Isolamento de Contratos:** Interfaces de serviço serão movidas para `App\Domain\Contracts` e prefixadas com `I` (ex: `IEmailSenderService`).
2.  **Padronização de Infraestrutura:** Implementações concretas serão movidas para `App\Infrastructure\Adapters` e renomeadas com o sufixo `Adapter` (ex: `S3AttachmentAdapter`).
3.  **Domain Services:** Serviços que contêm lógica de negócio serão movidos para `App\Domain\Services`.

### Riscos
- Necessidade de atualizar diversos *imports* e *Service Providers*.
- Risco de quebra de compatibilidade se não executado com cautela.

### Mitigação
- Refatoração incremental por serviço.
- Execução de testes após cada etapa.
