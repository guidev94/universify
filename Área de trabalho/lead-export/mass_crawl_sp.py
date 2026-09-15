
import time
import logging
from pipeline import LeadPipeline
from scheduler import scheduler
from blueprints.events import event_bus
from crawler.bing_crawler import BingCrawler

logging.basicConfig(level=logging.INFO)

def mass_crawl():
    sectors = [
        "Logística", "Transportadora", "Indústria", "Clínica Médica",
        "Escritório Contábil", "Advocacia", "Construtora", "Corretora de Seguros",
        "Distribuidora", "Autopeças", "Metalúrgica", "Gráfica",
        "Academia", "Restaurante", "Hotel"
    ]
    location = "São Paulo, SP"
    
    pipeline = LeadPipeline(event_bus=event_bus)
    pipeline.start()
    
    scheduler.pipeline = pipeline
    
    bing = BingCrawler()
    
    all_leads = []
    for sector in sectors:
        query = f"{sector} {location}"
        print(f"🔍 Searching for: {query}")
        try:
            leads = bing.crawl(query, 40)
            if leads:
                print(f"✅ Found {len(leads)} leads for {sector}")
                # Add location and campaign
                for l in leads:
                    l["location"] = location
                    l["campaign"] = "MASS_SP_QUALIF"
                
                # Submit to pipeline (which saves to DB and verifies)
                saved_ids = []
                for l in leads:
                    from models import Lead
                    lid = Lead.create(l)
                    if lid:
                        saved_ids.append({"id": lid, "website": l.get("website")})
                
                if saved_ids:
                    pipeline.submit_for_verification(saved_ids)
                    print(f"🚀 Submitted {len(saved_ids)} leads for verification")
        except Exception as e:
            print(f"❌ Error searching for {sector}: {e}")
        
        time.sleep(2) # Small delay between sectors

    print("⏳ Waiting for pipeline to process (120s)...")
    time.sleep(120)
    pipeline.stop()
    print("🏁 Mass crawl finished.")

if __name__ == "__main__":
    mass_crawl()
