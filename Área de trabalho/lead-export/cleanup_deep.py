import sqlite3
import os
import re

DB_PATH = os.path.join(os.path.dirname(__file__), "data", "leads.db")

def cleanup():
    print("Iniciando limpeza profunda de leads...")
    conn = sqlite3.connect(DB_PATH)
    conn.row_factory = sqlite3.Row
    cursor = conn.cursor()

    # 1. Remover leads com score < 70
    cursor.execute("DELETE FROM leads WHERE score < 70")
    deleted_low_score = cursor.rowcount
    print(f"Removidos {deleted_low_score} leads com pontuação baixa (< 70).")

    # 2. Remover leads com domínios claramente estrangeiros (TLDs não BR e não .com/.net/.org genéricos)
    # Buscamos domínios que não terminam em .br, .com, .net, .org, .co, .me, .io, .site, .online, .tech
    cursor.execute("SELECT id, website FROM leads WHERE website != ''")
    rows = cursor.fetchall()
    
    foreign_ids = []
    allowed_tlds = (".br", ".com", ".net", ".org", ".co", ".me", ".io", ".site", ".online", ".tech", ".store", ".shop")
    
    for row in rows:
        url = row["website"].lower()
        # Se tem site mas não termina em um TLD comum no Brasil e não é .br
        if not any(url.endswith(tld) or f"{tld}/" in url for tld in allowed_tlds):
            foreign_ids.append(row["id"])
            
    if foreign_ids:
        placeholders = ",".join("?" * len(foreign_ids))
        cursor.execute(f"DELETE FROM leads WHERE id IN ({placeholders})", foreign_ids)
        print(f"Removidos {len(foreign_ids)} leads com domínios estrangeiros suspeitos.")

    # 3. Remover leads com nomes em outros alfabetos (Chinês, Árabe, etc.)
    cursor.execute("SELECT id, name FROM leads")
    rows = cursor.fetchall()
    non_latin_ids = []
    for row in rows:
        if re.search(r"[\u4e00-\u9fff\u0400-\u04FF\u0600-\u06FF]", row["name"]):
            non_latin_ids.append(row["id"])
            
    if non_latin_ids:
        placeholders = ",".join("?" * len(non_latin_ids))
        cursor.execute(f"DELETE FROM leads WHERE id IN ({placeholders})", non_latin_ids)
        print(f"Removidos {len(non_latin_ids)} leads com caracteres não-latinos.")

    conn.commit()
    conn.close()
    print("Limpeza concluída!")

if __name__ == "__main__":
    cleanup()
