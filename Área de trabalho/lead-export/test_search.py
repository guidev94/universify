
import logging
import sys
import os

# Adiciona o diretório atual ao sys.path
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

from crawler.duckduckgo_crawler import DuckDuckGoCrawler
from crawler.bing_crawler import BingCrawler
from crawler.maps_crawler import MapsCrawler
from crawler.validator import validate_lead

logging.basicConfig(level=logging.INFO)

def test_crawlers():
    query = "dentista"
    location = "Ipiranga São Paulo"
    quantity = 5

    print(f"--- Testando DuckDuckGo ---")
    ddg = DuckDuckGoCrawler()
    leads_ddg = ddg.crawl(query, quantity)
    print(f"DuckDuckGo encontrou {len(leads_ddg)} leads")
    for l in leads_ddg:
        print(f"  - {l['name']} | {l['email']} | {l['phone']} | {l['website']}")
        v = validate_lead(l)
        print(f"    Validado: {v is not None}")

    print(f"\n--- Testando Bing ---")
    bing = BingCrawler()
    leads_bing = bing.crawl(query, quantity)
    print(f"Bing encontrou {len(leads_bing)} leads")
    for l in leads_bing:
        print(f"  - {l['name']} | {l['email']} | {l['phone']} | {l['website']}")
        v = validate_lead(l)
        print(f"    Validado: {v is not None}")

    # Maps test might be slow or require display
    # print(f"\n--- Testando Maps ---")
    # maps = MapsCrawler()
    # leads_maps = maps.crawl(query, location, quantity)
    # print(f"Maps encontrou {len(leads_maps)} leads")

if __name__ == "__main__":
    test_crawlers()
