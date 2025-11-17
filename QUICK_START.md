# ⚡ Quick Start - IDLE Worker

## 🚀 Rodar em 2 Passos

### 1. Subir Serviços

```bash
# Subir Redis e Worker
docker-compose up -d redis idler-worker
```

### 2. Ver Logs

```bash
docker-compose logs -f idler-worker
```

## ✅ O que você deve ver nos logs:

```
✅ Conectado ao Redis
📋 Buscando contas ativas...
✅ 2 conta(s) encontrada(s)
🔌 Conectando: email@exemplo.com...
✅ Conectado: email@exemplo.com
📬 INBOX aberto
👂 IDLE ativo [email@exemplo.com]
```

## 🧪 Testar

1. **Envie um email** para uma conta monitorada
2. **Veja nos logs:**
   ```
   📧 1 novo(s) email(s)
   ✅ Email salvo: "Assunto"
   ```
3. **Verifique no banco:**
   ```bash
   docker exec -it smail_postgres_db psql -U user -d staging -c "SELECT subject, \"from\", created_at FROM emails ORDER BY created_at DESC LIMIT 1;"
   ```

## 🔧 Comandos Úteis

```bash
# Ver logs
docker-compose logs -f idler-worker

# Reiniciar
docker-compose restart idler-worker

# Rebuild (após mudanças)
docker-compose build idler-worker
docker-compose up -d idler-worker

# Ver fila Redis
docker exec smail_redis redis-cli LLEN email:processing:queue
```

## ❌ Problemas?

**Worker não conecta:**
- Verifique Redis: `docker-compose ps redis`
- Verifique API: `curl http://localhost:8085/api/ping`

**Nenhuma conta:**
- Verifique banco: contas com `active=true` e `password` preenchido
- Teste endpoint: `curl http://localhost:8085/api/accounts/active`

