
import requests
from crawler.maps_crawler import MapsCrawler
import logging

logging.basicConfig(level=logging.INFO)

crawler = MapsCrawler()
leads = crawler.crawl("Logística", "São Paulo, SP", 10)
print(f"Encontrados {len(leads)} leads")
for l in leads:
    print(l)
