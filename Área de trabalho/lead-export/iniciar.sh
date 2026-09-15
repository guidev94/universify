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
pip install gunicorn flask-compress

# 3. Limpar processos antigos e verificar porta 5000
echo -e "${BLUE}[3/3] Limpando ambiente e verificando porta 5000...${NC}"
pkill -f "python3 app.py" 2>/dev/null
pkill -f "gunicorn" 2>/dev/null
pkill -f "chrome" 2>/dev/null
pkill -f "chromedriver" 2>/dev/null

PORT_BUSY=$(lsof -t -i :5000)
if [ ! -z "$PORT_BUSY" ]; then
    echo -e "${RED}[AVISO] Encerrando processo na porta 5000 (PID: $PORT_BUSY)...${NC}"
    kill -9 $PORT_BUSY 2>/dev/null
fi

# 4. Iniciar o projeto
echo -e "${GREEN}Iniciando servidor em http://localhost:5000...${NC}"
echo -e "${BLUE}Logs serão exibidos abaixo. Pressione CTRL+C para parar.${NC}"
echo ""

python3 app.py
