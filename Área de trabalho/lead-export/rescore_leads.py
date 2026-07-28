import os
import sys
from database import get_connection
from crawler.lead_scorer import calculate_score
from models import Lead

def rescore_all():
    print("Iniciando recalculo de scores para todos os leads...")
    conn = get_connection()
    leads = conn.execute("SELECT * FROM leads").fetchall()
    conn.close()
    
    count = 0
    updated_list = []
    to_delete = []
    
    from crawler.validator import _is_brazilian
    
    for row in leads:
        lead_data = dict(row)
        count += 1
        
        # Check if it's brazilian with the new stricter logic
        if not _is_brazilian(lead_data.get("website"), lead_data.get("phone"), lead_data.get("email")):
            to_delete.append(lead_data["id"])
            continue
            
        score, tier, pitch_hook = calculate_score(lead_data)
        
        if score != lead_data["score"] or tier != lead_data["tier"] or pitch_hook != lead_data.get("pitch_hook"):
            updated_list.append((score, tier, pitch_hook, lead_data["id"]))
    
    print(f"Processando mudanças no banco...")
    conn = get_connection()
    
    # Process deletions
    for lid in to_delete:
        conn.execute("DELETE FROM leads WHERE id = ?", (lid,))
    
    # Process updates
    for score, tier, pitch_hook, lid in updated_list:
        conn.execute(
            "UPDATE leads SET score = ?, tier = ?, pitch_hook = ?, updated_at = datetime('now', 'localtime') WHERE id = ?",
            (score, tier, pitch_hook, lid)
        )
    
    conn.commit()
    conn.close()
    print(f"Processamento concluído!")
    print(f"Total de leads analisados: {count}")
    print(f"Leads atualizados: {len(updated_list)}")
    print(f"Leads 'gringos' removidos: {len(to_delete)}")

if __name__ == "__main__":
    rescore_all()
