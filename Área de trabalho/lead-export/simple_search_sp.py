
import requests
from bs4 import BeautifulSoup
import re
from pipeline import LeadPipeline
from blueprints.events import event_bus
from models import Lead
import time

def find_sp_leads():
    # Simple search for SP business sites
    queries = ["logistica sao paulo", "industria sao paulo", "clinica sao paulo", "advogado sao paulo", "construtora sao paulo"]
    all_urls = []
    
    for q in queries:
        print(f"Searching: {q}")
        # Use a simple search engine request
        url = f"https://www.bing.com/search?q={q.replace(' ', '+')}"
        headers = {"User-Agent": "Mozilla/5.0"}
        try:
            res = requests.get(url, headers=headers, timeout=10)
            soup = BeautifulSoup(res.text, "lxml")
            for a in soup.select("li.b_algo h2 a"):
                href = a.get("href")
                if href and "http" in href and ".br" in href:
                    all_urls.append(href)
        except:
            pass
            
    print(f"Found {len(all_urls)} potential URLs")
    
    pipeline = LeadPipeline(event_bus=event_bus)
    pipeline.start()
    
    leads_to_verify = []
    for url in all_urls:
        lead = {
            "name": url.split("//")[-1].split(".")[0].title(),
            "website": url,
            "location": "São Paulo, SP",
            "campaign": "USER_REQUEST_SP"
        }
        lid = Lead.create(lead)
        if lid:
            leads_to_verify.append({"id": lid, "website": url})
            
    if leads_to_verify:
        pipeline.submit_for_verification(leads_to_verify)
        print(f"Submitted {len(leads_to_verify)} for verification")
        
    time.sleep(60)
    pipeline.stop()

if __name__ == "__main__":
    find_sp_leads()
