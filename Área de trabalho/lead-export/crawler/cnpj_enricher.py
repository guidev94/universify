import requests
import json
import logging
import os
from database import get_connection

logger = logging.getLogger("leadhunter.cnpj")

class CNPJEnricher:
    """Enriches leads with CNPJ data using BrasilAPI."""
    
    def __init__(self):
        self.api_url = "https://brasilapi.com.br/api/cnpj/v1/"
        self.timeout = 10

    def enrich_lead(self, lead_id, company_name):
        """Attempts to find CNPJ via Google Search and then enrich using BrasilAPI."""
        if not company_name or len(company_name) < 3:
            return False

        from crawler.search_engine import SearchEngine
        import re
        
        try:
            se = SearchEngine()
            # Search for CNPJ on Google
            search_query = f'cnpj "{company_name}"'
            urls = se.search(search_query, num_results=5)
            
            cnpj_pattern = re.compile(r"\d{2}\.\d{3}\.\d{3}/\d{4}-\d{2}")
            
            for url in urls:
                # Many sites like cnpj.biz, casadosdados, etc have the CNPJ in the title or URL
                match = cnpj_pattern.search(url)
                if match:
                    cnpj = match.group(0)
                    return self.enrich_by_cnpj(lead_id, cnpj)
                
                # If not in URL, try a quick request to the page if it's a known directory
                if any(domain in url for domain in ["cnpj.biz", "casadosdados.com.br", "econodata.com.br"]):
                    res = requests.get(url, timeout=5)
                    match = cnpj_pattern.search(res.text)
                    if match:
                        cnpj = match.group(0)
                        return self.enrich_by_cnpj(lead_id, cnpj)
        except Exception as e:
            logger.debug(f"[cnpj] search heuristic failed: {e}")
            
        return False

    def enrich_by_cnpj(self, lead_id, cnpj):
        cnpj_clean = "".join(filter(str.isdigit, cnpj))
        if len(cnpj_clean) != 14:
            return False

        try:
            response = requests.get(f"{self.api_url}{cnpj_clean}", timeout=self.timeout)
            if response.status_code == 200:
                data = response.json()
                
                partners = []
                for qsa in data.get("qsa", []):
                    partners.append(f"{qsa.get('nome_socio')} ({qsa.get('qualificacao_socio')})")
                
                partner_str = "; ".join(partners)
                capital = data.get("capital_social", 0)
                legal_nature = data.get("natureza_juridica", "")
                
                conn = get_connection()
                conn.execute(
                    """UPDATE leads SET 
                       cnpj = ?, 
                       partners = ?, 
                       company_value = ?,
                       legal_nature = ?,
                       updated_at = datetime('now', 'localtime') 
                       WHERE id = ?""",
                    (cnpj_clean, partner_str, capital, legal_nature, lead_id)
                )
                conn.commit()
                conn.close()
                logger.info(f"[cnpj] Lead {lead_id} enriquecido com CNPJ {cnpj_clean}")
                return True
        except Exception as e:
            logger.error(f"[cnpj] Erro ao enriquecer lead {lead_id}: {e}")
        
        return False
