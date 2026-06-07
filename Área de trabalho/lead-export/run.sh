#!/bin/bash
cd "$(dirname "$0")"
source venv/bin/activate

MODE="${1:-dev}"

echo "╔══════════════════════════════════════════╗"
echo "║   LeadHunter — Servidor 24/7            ║"
echo "║   http://localhost:5000                 ║"
echo "║   Mode: $MODE                           ║"
echo "║   Scheduler: parallel (6 tasks)         ║"
echo "║   Pipeline: verify → enrich (real-time) ║"
echo "║   Ctrl+C para parar                    ║"
echo "╚══════════════════════════════════════════╝"

if [ "$MODE" = "prod" ]; then
    exec gunicorn app:app \
        --bind 0.0.0.0:5000 \
        --workers 2 \
        --threads 4 \
        --timeout 300 \
        --graceful-timeout 30 \
        --keep-alive 30 \
        --access-logfile - \
        --error-logfile -
else
    while true; do
        python app.py
        echo "[$(date)] Server parou. Reiniciando em 3s..."
        sleep 3
    done
fi
