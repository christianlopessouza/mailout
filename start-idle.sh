#!/bin/bash

echo "🚀 Iniciando IDLE Worker..."
echo ""

# Verifica se docker-compose está disponível
if ! command -v docker-compose &> /dev/null; then
    echo "❌ docker-compose não encontrado"
    exit 1
fi

# Verifica se há arquivo .env.docker
if [ ! -f "docker/.env.docker" ]; then
    echo "⚠️ Arquivo docker/.env.docker não encontrado"
    echo "Criando arquivo básico..."
    mkdir -p docker
    cat > docker/.env.docker << EOF
# Token do cliente (obtenha do banco: SELECT token FROM clients LIMIT 1;)
CLIENT_TOKEN=

# Token interno (opcional)
INTERNAL_API_TOKEN=
EOF
    echo "✅ Arquivo criado. Edite docker/.env.docker e adicione o CLIENT_TOKEN"
    echo ""
fi

# Verifica se Redis está rodando
echo "1️⃣ Verificando Redis..."
if docker ps | grep -q smail_redis; then
    echo "   ✅ Redis já está rodando"
else
    echo "   📦 Subindo Redis..."
    docker-compose up -d redis
    sleep 2
fi

# Verifica se app está rodando
echo ""
echo "2️⃣ Verificando Laravel..."
if docker ps | grep -q smail_laravel_app; then
    echo "   ✅ Laravel já está rodando"
else
    echo "   📦 Subindo Laravel..."
    docker-compose up -d app
    sleep 5
fi

# Testa endpoint de contas
echo ""
echo "3️⃣ Testando endpoint de contas..."
sleep 2
ACCOUNTS=$(curl -s http://localhost:8085/api/accounts/active 2>/dev/null || curl -s http://localhost:8080/api/accounts/active 2>/dev/null)
if [ $? -eq 0 ] && [ ! -z "$ACCOUNTS" ]; then
    COUNT=$(echo "$ACCOUNTS" | grep -o '"id"' | wc -l)
    echo "   ✅ API acessível - $COUNT conta(s) encontrada(s)"
else
    echo "   ⚠️ API não acessível ou sem contas"
    echo "   Verifique se há contas no banco com active=true"
fi

# Build do worker
echo ""
echo "4️⃣ Construindo worker IDLE..."
docker-compose build idler-worker

# Sobe o worker
echo ""
echo "5️⃣ Iniciando worker IDLE..."
docker-compose up -d idler-worker

# Aguarda um pouco
sleep 3

# Verifica status
echo ""
echo "6️⃣ Status dos serviços:"
docker-compose ps | grep -E "redis|idler|app"

echo ""
echo "✅ Pronto!"
echo ""
echo "Para ver logs do worker:"
echo "  docker-compose logs -f idler-worker"
echo ""
echo "Para verificar fila Redis:"
echo "  docker exec smail_redis redis-cli LLEN email:processing:queue"
echo ""

