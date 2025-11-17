FROM node:20-alpine AS builder

WORKDIR /app

# Instala dependências do sistema (necessário para algumas libs nativas)
RUN apk add --no-cache python3 make g++

# Copia arquivos de dependências
COPY idler/package*.json ./

# Instala todas as dependências (incluindo dev)
RUN npm install

# Copia código fonte
COPY idler/src ./src
COPY idler/tsconfig.json ./

# Compila TypeScript
RUN npm run build

# Remove arquivos de desenvolvimento
RUN rm -rf src tsconfig.json

# Stage de produção
FROM node:20-alpine

WORKDIR /app

# Instala apenas dependências de produção
COPY idler/package*.json ./
RUN npm install --only=production && npm cache clean --force

# Copia arquivos compilados do builder
COPY --from=builder /app/dist ./dist

# Usa usuário root (pode mudar depois se necessário)
# Para produção, considere criar usuário específico

# Roda worker
CMD ["node", "dist/index.js"]

