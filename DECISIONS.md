# Decisoes do Projeto

> **Bernardo Merlo (@PM):** Fase ativa: Fase 1 - Discovery reconstruida a partir do codigo existente. Pendente: validar com o dono do projeto se os comportamentos documentados representam o produto desejado ou apenas o estado atual da implementacao.
> **Nicolas Oliveira (@BA):** As regras abaixo foram inferidas de rotas, use cases, entidades, migrations e testes. Onde houver divergencia entre intencao e codigo, a decisao deve ser revisada.
> **Branas (@ARCH):** Concordo em documentar o sistema como ele existe hoje. Risco: ha trechos legados e duplicados; vamos registrar isso como risco assumido, nao como padrao ideal.
> **Devandro (@FOOL):** Antes de expandir feature, vale perguntar se o produto e "API de envio" ou "plataforma de caixa postal multi-conta". Hoje o codigo parece tentar ser os dois.

## DEC-001 - Documentar o estado atual antes de alterar regra de negocio

**Data:** 2026-05-18  
**Status:** Aceita  
**Proponente:** `@PM`  
**Participantes:** `@PM`, `@BA`, `@ARCH`, `@DEV`, `@QA`, `@TM`

### Contexto
O repositorio ja possui implementacao Laravel/PHP, worker IDLE em Node.js/TypeScript, migrations, testes e uma ADR sobre RabbitMQ, mas nao possui `DECISIONS.md`, `TASKS.md` nem especificacao consolidada de regras de negocio.

### Decisao
Criar uma camada inicial de documentacao, inspirada na estrutura do projeto `practice-dailydiet`, contendo:
- requisitos funcionais;
- regras de negocio;
- PRD e user stories;
- diagramas Mermaid;
- decisoes arquiteturais;
- plano inicial, QA e seguranca;
- backlog em `TASKS.md`.

### Alternativas Consideradas
- Criar apenas um README novo: simples, mas misturaria regra, arquitetura e backlog.
- Refatorar o codigo antes de documentar: arriscado sem contrato de negocio validado.
- Documentar somente rotas: insuficiente para capturar regras de dominio.

### Riscos
- Algumas regras documentadas sao inferencias do codigo e podem refletir comportamento acidental.
- Existem inconsistencias entre Redis e RabbitMQ na documentacao/infra do worker.
- Existem endpoints internos/temporarios sem autenticacao forte.

## DEC-002 - Autenticacao por token de cliente e token de conta

**Data:** 2026-05-18  
**Status:** Existente no codigo  
**Proponente:** `@ARCH`  
**Participantes:** `@ARCH`, `@SEC`, `@BA`

### Contexto
As rotas publicas de cliente usam bearer token para localizar `Client`. As rotas publicas de conta usam bearer token do cliente e parametro `account` para localizar `Account`.

### Decisao
Manter o modelo atual documentado:
- Cliente autentica por bearer token.
- Conta autentica por token enviado no campo `account`.
- A conta so pode operar se seu email pertencer ao dominio do cliente autenticado.

### Riscos
- O token de conta no corpo da requisicao e mais facil de vazar em logs do que um header dedicado.
- Falta documentar expiracao, rotacao e revogacao de tokens.

## DEC-003 - RabbitMQ como direcao para fila confiavel

**Data:** 2026-05-18  
**Status:** Reforca ADR existente  
**Proponente:** `@OPS`  
**Participantes:** `@OPS`, `@ARCH`, `@DEV`

### Contexto
O repositorio possui `docs/architecture/ADR-001-rabbit-mq.md`, servico RabbitMQ no Docker Compose e publicacao em `account_sync_queue`. Ao mesmo tempo, o README do worker IDLE ainda menciona Redis.

### Decisao
Considerar RabbitMQ a decisao arquitetural alvo para eventos confiaveis e manter Redis como legado/pendencia de alinhamento.

### Riscos
- Inconsistencia operacional enquanto worker, docs e compose nao estiverem totalmente alinhados.
- Entrega at-least-once exige idempotencia nos consumidores.

## DEC-004 - Sanitizar credenciais versionadas e exigir variaveis de ambiente para comandos legados

**Data:** 2026-05-19  
**Status:** Aceita  
**Proponente:** `@SEC`  
**Issue:** [#6](https://github.com/christianlopessouza/mailout/issues/6)  
**Participantes:** `@SEC`, `@OPS`, `@DEV`, `@ARCH`

### Contexto
Arquivos versionados continham host de banco real, senhas como fallback em `env(...)` e credenciais SMTP/AWS-style em comandos legados de migracao/sincronizacao.

### Decisao
Substituir defaults sensiveis por valores locais/placeholders e exigir que comandos legados recebam credenciais por variaveis de ambiente `LEGACY_*`.

### Alternativas Consideradas
- Remover totalmente comandos legados: reduziria superficie, mas poderia bloquear migracoes ainda necessarias sem decisao de produto/operacao.
- Manter defaults reais para conveniencia: rejeitado por vazar segredos e perpetuar credenciais comprometidas.

### Riscos
- Ambientes que dependiam dos defaults reais precisam configurar variaveis explicitamente.
- A remocao do codigo nao limpa o historico Git; as credenciais expostas precisam ser rotacionadas nos provedores.
