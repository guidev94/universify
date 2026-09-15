
import logging
import sys
import os

# Adiciona o diretório atual ao sys.path
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

from crawler.enrichment_crawler import EnrichmentCrawler

logging.basicConfig(level=logging.INFO)

def test_enrichment():
    lead = {
        "id": 1,
        "name": "Hospital Odontológico",
        "website": "https://www.hospitalodontologico.com.br/",
        "email": "",
        "phone": "(71) 4102-7244"
    }

    print(f"--- Testando Enriquecimento ---")
    enricher = EnrichmentCrawler()
    data = enricher.enrich_lead(lead)
    
    print(f"Dados enriquecidos: {data}")

if __name__ == "__main__":
    test_enrichment()
