#!/bin/bash

echo "🧪 Testando configuração do IDLE Worker..."
echo ""

# Verifica se Redis está acessível
echo "1️⃣ Testando conexão com Redis..."
if command -v redis-cli &> /dev/null; then
    redis-cli -h localhost -p 6379 ping > /dev/null 2>&1
    if [ $? -eq 0 ]; then
        echo "   ✅ Redis está acessível"
    else
        echo "   ❌ Redis não está acessível. Certifique-se de que está rodando:"
        echo "      docker-compose up -d redis"
    fi
else
    echo "   ⚠️ redis-cli não encontrado. Testando via docker..."
    docker exec smail_redis redis-cli ping > /dev/null 2>&1
    if [ $? -eq 0 ]; then
        echo "   ✅ Redis está rodando no Docker"
    else
        echo "   ❌ Redis não está rodando"
    fi
fi

echo ""
echo "2️⃣ Testando endpoint de contas..."
API_RESPONSE=$(curl -s http://localhost:8085/api/accounts/active)
if [ $? -eq 0 ]; then
    echo "   ✅ API está acessível"
    ACCOUNT_COUNT=$(echo $API_RESPONSE | grep -o '"id"' | wc -l)
    echo "   📊 Contas encontradas: $ACCOUNT_COUNT"
    if [ $ACCOUNT_COUNT -gt 0 ]; then
        echo "   ✅ Há contas ativas para monitorar"
        echo ""
        echo "   Primeira conta:"
        echo $API_RESPONSE | head -c 200
        echo "..."
    else
        echo "   ⚠️ Nenhuma conta ativa encontrada"
        echo "   Certifique-se de que há contas no banco com active=true e password preenchido"
    fi
else
    echo "   ❌ API não está acessível"
    echo "   Certifique-se de que o Laravel está rodando:"
    echo "      docker-compose up -d app"
fi

echo ""
echo "3️⃣ Verificando fila Redis..."
if command -v redis-cli &> /dev/null; then
    QUEUE_SIZE=$(redis-cli -h localhost -p 6379 LLEN email:processing:queue 2>/dev/null)
    echo "   📊 Emails na fila: $QUEUE_SIZE"
else
    QUEUE_SIZE=$(docker exec smail_redis redis-cli LLEN email:processing:queue 2>/dev/null)
    echo "   📊 Emails na fila: $QUEUE_SIZE"
fi

echo ""
echo "✅ Teste concluído!"
echo ""
echo "Para iniciar o worker:"
echo "  docker-compose up -d idler-worker"
echo ""
echo "Para ver logs:"
echo "  docker-compose logs -f idler-worker"

