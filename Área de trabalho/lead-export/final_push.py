
import time
import logging
from pipeline import LeadPipeline
from blueprints.events import event_bus
from crawler.directory_crawler import DirectoryCrawler
from models import Lead

logging.basicConfig(level=logging.INFO)

def final_push():
    sectors = ["Logística", "Transportadora", "Clínica Médica", "Advocacia", "Construtora", "Contabilidade"]
    location = "São Paulo, SP"
    
    pipeline = LeadPipeline(event_bus=event_bus)
    pipeline.start()
    
    crawler = DirectoryCrawler()
    
    total_found = 0
    for sector in sectors:
        print(f"🔍 Searching GuiaMais for: {sector} in {location}")
        try:
            # Crawl GuiaMais directly
            leads = crawler._crawl_guiamais(sector, location, 30)
            if leads:
                print(f"✅ Found {len(leads)} leads for {sector}")
                total_found += len(leads)
                
                saved_ids = []
                for l in leads:
                    l["location"] = location
                    l["campaign"] = "FINAL_QUALIF_SP"
                    lid = Lead.create(l)
                    if lid:
                        saved_ids.append({"id": lid, "website": l.get("website")})
                
                if saved_ids:
                    pipeline.submit_for_verification(saved_ids)
                    print(f"🚀 Submitted {len(saved_ids)} leads for verification")
        except Exception as e:
            print(f"❌ Error: {e}")
        
        if total_found >= 150:
            break
            
    print("⏳ Waiting for pipeline to process (90s)...")
    time.sleep(90)
    pipeline.stop()
    print(f"🏁 Final push finished. Total found: {total_found}")

if __name__ == "__main__":
    final_push()
