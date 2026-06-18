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
        """
        Attempts to find CNPJ and partners for a company name.
        Note: This is a heuristic search. Real CNPJ search by name usually requires 
        different APIs or web scraping. This implementation focuses on enriching 
        if we already have a CNPJ or using a placeholder for the logic.
        """
        # Placeholder for real search logic. 
        # In a production environment, we might use a search engine to find the CNPJ first.
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
