#!/bin/bash

# Cores para o output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${BLUE}╔══════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║          LeadHunter — Inicialização Rápida           ║${NC}"
echo -e "${BLUE}╚══════════════════════════════════════════════════════╝${NC}"

# 1. Verificar se o venv existe
if [ ! -d "venv" ]; then
    echo -e "${BLUE}[1/3] Criando ambiente virtual (venv)...${NC}"
    python3 -m venv venv
    if [ $? -ne 0 ]; then
        echo -e "${RED}Erro ao criar venv. Verifique se o python3-venv está instalado.${NC}"
        exit 1
    fi
else
    echo -e "${GREEN}[OK] Ambiente virtual detectado.${NC}"
fi

# 2. Ativar venv e instalar dependências
echo -e "${BLUE}[2/3] Verificando dependências...${NC}"
source venv/bin/activate
pip install -r requirements.txt
pip install gunicorn  # Garante que gunicorn está disponível para prod

# 3. Verificar porta 5000
PORT_BUSY=$(lsof -i :5000)
if [ ! -z "$PORT_BUSY" ]; then
    echo -e "${RED}[AVISO] A porta 5000 já parece estar em uso.${NC}"
    echo "$PORT_BUSY"
    echo -e "${BLUE}Deseja tentar encerrar o processo na porta 5000? (s/n)${NC}"
    read -r response
    if [[ "$response" =~ ^([sS][iI]|[sS])$ ]]; then
        fuser -k 5000/tcp
    fi
fi

# 4. Iniciar o projeto
echo -e "${GREEN}[3/3] Iniciando servidor em http://localhost:5000...${NC}"
echo -e "${BLUE}Logs serão exibidos abaixo. Pressione CTRL+C para parar.${NC}"
echo ""

python3 app.py
