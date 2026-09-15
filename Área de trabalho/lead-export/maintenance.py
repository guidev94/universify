
import sqlite3
import logging
from database import get_connection
from crawler.lead_scorer import calculate_score

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger("leadhunter.maintenance")

def cleanup_leads():
    conn = get_connection()
    try:
        # 1. Hard Delete Gov, Org, Edu and clearly non-target domains
        bad_patterns = ["%.gov.br%", "%.org.br%", "%.edu.br%", "%prefeitura%", "%camara%", "%jus.br%"]
        total_removed = 0
        for pattern in bad_patterns:
            res = conn.execute("DELETE FROM leads WHERE website LIKE ?", (pattern,))
            total_removed += res.rowcount
            
        logger.info(f"🗑️ Removidos {total_removed} leads governamentais/não-comerciais.")

        # 2. Re-score and filter by quality/language
        rows = conn.execute("SELECT * FROM leads").fetchall()
        leads = [dict(r) for r in rows]
        
        disqualified = 0
        updated = 0
        
        for lead in leads:
            score, tier, hook = calculate_score(lead)
            
            # If score is 0 or it's marked as disqualified in the hook
            if score < 10 or "Disqualificado" in hook:
                conn.execute("DELETE FROM leads WHERE id = ?", (lead["id"],))
                disqualified += 1
            else:
                conn.execute(
                    "UPDATE leads SET score = ?, tier = ?, pitch_hook = ? WHERE id = ?",
                    (score, tier, hook, lead["id"])
                )
                updated += 1
        
        conn.commit()
        logger.info(f"🧹 Limpeza concluída: {disqualified} removidos, {updated} atualizados.")
        
    finally:
        conn.close()

if __name__ == "__main__":
    cleanup_leads()
