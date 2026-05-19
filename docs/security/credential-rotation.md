# Credential Rotation Note - Issue #6

> **Vitor Lozorio (@SEC):** Credenciais reais foram encontradas em arquivos versionados. Remover do codigo reduz exposicao futura, mas nao invalida segredos ja vazados no historico.
> **Edinho (@OPS):** A acao operacional correta e rotacionar os acessos afetados nos provedores e atualizar os ambientes com variaveis seguras.

## Segredos que precisam de rotacao

- Credenciais do banco PostgreSQL principal expostas em `.env.example`, `config/database.php` e comandos de sincronizacao.
- Credenciais dos bancos MySQL legados expostas em `config/database.php` e comandos de sincronizacao.
- Credenciais SMTP/AWS-style usadas por comandos legados de migracao de contas.

## Decisao operacional

- Arquivos versionados devem conter apenas placeholders ou defaults locais.
- Comandos de migracao/sincronizacao legados devem ler credenciais via variaveis de ambiente.
- Nenhuma credencial ja publicada deve ser reutilizada sem rotacao.

## Variaveis esperadas para comandos legados

- `LEGACY_MIGRATION_SMTP_HOST`
- `LEGACY_MIGRATION_SMTP_PORT`
- `LEGACY_MIGRATION_SMTP_USERNAME`
- `LEGACY_MIGRATION_SMTP_PASSWORD`
- `LEGACY_SYNC_PGSQL_HOST`
- `LEGACY_SYNC_PGSQL_PORT`
- `LEGACY_SYNC_PGSQL_DATABASE`
- `LEGACY_SYNC_PGSQL_USERNAME`
- `LEGACY_SYNC_PGSQL_PASSWORD`
- `LEGACY_SYNC_PGSQL_SSLMODE`
- `LEGACY_SYNC_MYSQL_HOST`
- `LEGACY_SYNC_MYSQL_PORT`
- `LEGACY_SYNC_MYSQL_DATABASE`
- `LEGACY_SYNC_MYSQL_USERNAME`
- `LEGACY_SYNC_MYSQL_PASSWORD`
