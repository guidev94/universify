#!/bin/bash
# LeadHunter Pro Start Script

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${BLUE}Ativando LeadHunter Pro...${NC}"

# 1. Cleanup
pkill -9 -f "python3 app.py" 2>/dev/null
pkill -9 -f "gunicorn" 2>/dev/null
pkill -9 -f "chrome" 2>/dev/null
pkill -9 -f "chromedriver" 2>/dev/null

# 2. Database Unlock
sqlite3 data/leads.db "PRAGMA wal_checkpoint(TRUNCATE);" 2>/dev/null

# 3. Dependencies
source venv/bin/activate
pip install gunicorn flask-compress --quiet

# 4. Start Server with Gunicorn (4 threads for concurrency)
echo -e "${GREEN}Iniciando Gunicorn em http://0.0.0.0:5000...${NC}"
nohup gunicorn -w 1 --threads 8 --timeout 120 --bind 0.0.0.0:5000 app:app > server.log 2>&1 &

echo -e "${GREEN}Sistema ATIVADO!${NC}"
echo "Logs: tail -f server.log"
