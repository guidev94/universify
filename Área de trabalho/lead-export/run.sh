#!/bin/bash
cd "$(dirname "$0")"
source venv/bin/activate

echo "╔══════════════════════════════════════╗"
echo "║   LeadHunter — Servidor 24/7        ║"
echo "║   http://localhost:5000             ║"
echo "║   Auto-crawl: a cada 3 horas       ║"
echo "║   Ctrl+C para parar                ║"
echo "╚══════════════════════════════════════╝"

while true; do
    python app.py
    echo "[$(date)] Server parou. Reiniciando em 5s..."
    sleep 5
done
