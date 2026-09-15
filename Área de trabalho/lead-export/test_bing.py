
from crawler.bing_crawler import BingCrawler
import logging

logging.basicConfig(level=logging.INFO)

crawler = BingCrawler()
leads = crawler.crawl("Logística São Paulo", 20)
print(f"Encontrados {len(leads)} leads")
for l in leads:
    print(l)
