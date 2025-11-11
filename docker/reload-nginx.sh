#!/bin/bash
# Script para recarregar nginx e aplicar mudanças de CORS

echo "Copiando nginx.conf para o container..."
docker cp docker/nginx/nginx.conf smail_laravel_app:/etc/nginx/sites-available/default

echo "Testando configuração do nginx..."
docker exec smail_laravel_app nginx -t

echo "Recarregando nginx..."
docker exec smail_laravel_app nginx -s reload

echo "Verificando se nginx está rodando..."
docker exec smail_laravel_app ps aux | grep nginx

echo "Concluído! Teste a requisição novamente."


