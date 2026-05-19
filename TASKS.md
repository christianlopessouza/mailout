# TASKS

> **Theo (@TM):** Backlog inicial criado a partir da analise do repositorio. IDs canonicos dos responsaveis foram mantidos para rastreabilidade.

## Contexto da Sessao

- **Fase ativa:** Fase 1 - Discovery documentado a partir do codigo existente.
- **Pendente:** validacao do usuario sobre regras inferidas, priorizacao do backlog e saneamento de riscos.
- **Quem esta na sala:** `@PM`, `@BA`, `@ARCH`, `@DBA`, `@DEV`, `@QA`, `@OPS`, `@SEC`, `@TM`.

## Backlog

| ID | Status | Responsavel | Fase | Tarefa | Criterio de aceite |
|---|---|---|---|---|---|
| T-001 | Done | `@BA` | Discovery | Levantar regras de negocio atuais a partir do codigo | `docs/specs/business-rules.md` criado |
| T-002 | Done | `@BA` | Requirements | Criar requisitos funcionais no formato do `practice-dailydiet` | `docs/specs/functional-requirements.md` criado |
| T-003 | Done | `@ARCH` | Architecture | Registrar stack e arquitetura inicial | `docs/architecture/ADR-002-stack-and-architecture.md` criado |
| T-004 | Done | `@DBA` | Architecture | Mapear entidades e relacoes principais | `docs/diagrams/erd.md` criado |
| T-005 | Done | `@ARCH` | Architecture | Mapear fluxo de componentes | `docs/diagrams/architecture.md` criado |
| T-006 | Todo | `@PM` | Discovery | Validar se produto e API transacional, cliente de email multi-conta ou ambos | Atualizar PRD com posicionamento confirmado |
| T-007 | Todo | `@SEC` | QA & Security | Revisar endpoints internos sem autenticacao | Checklist com mitigacoes aprovado |
| T-008 | Todo | `@OPS` | Deploy & Docs | Alinhar Redis/RabbitMQ nos docs e no worker IDLE | README do IDLE, compose e codigo sem contradicao |
| T-009 | Todo | `@QA` | QA & Security | Transformar regras de negocio criticas em testes de aceitacao | Testes cobrindo dominio, autenticacao, envio e recebimento |
| T-010 | Todo | `@DEV` | Development | Remover endpoint temporario ou proteger por ambiente | `/test-update-email-complement/{id}` indisponivel em producao |
| T-011 | Done | `@TM` | Sprint Planning | Levantar melhorias candidatas a GitHub Issues | `docs/planning/github-project-issues.md` criado |
| T-012 | Todo | `@TM` | Sprint Planning | Criar issues no GitHub Projects a partir do levantamento | Issues criadas e vinculadas ao projeto |

## Execucao de Issues

| Issue | Status | Branch | Responsavel | Resumo |
|---|---|---|---|---|
| [#6](https://github.com/christianlopessouza/mailout/issues/6) | In progress | `IS#6/removeCommittedRealCredentials` | `@SEC`, `@DEV`, `@OPS` | Sanitizar credenciais versionadas, documentar rotacao e validar regressao por teste/varredura |
