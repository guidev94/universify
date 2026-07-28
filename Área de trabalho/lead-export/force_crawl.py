
import time
import logging
from scheduler import scheduler
from pipeline import LeadPipeline
from blueprints.events import event_bus

logging.basicConfig(level=logging.INFO)

# Setup scheduler with pipeline and event bus
pipeline = LeadPipeline(event_bus=event_bus)
pipeline.start()
scheduler.pipeline = pipeline
scheduler.event_bus = event_bus

print("🚀 Iniciando extração forçada para destravar resultados...")

# Trigger campaign fill immediately
try:
    print("📋 Processando campanhas ativas...")
    scheduler._crawl_campaigns()
    print("✅ Ciclo de campanhas concluído.")
    
    print("🔍 Iniciando auto-crawl de keywords...")
    scheduler._auto_crawl()
    print("✅ Auto-crawl concluído.")
    
except Exception as e:
    print(f"❌ Erro durante extração: {e}")

print("⏳ Aguardando pipeline processar leads (10s)...")
time.sleep(10)
pipeline.stop()
print("🏁 Finalizado!")
