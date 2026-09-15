import sqlite3
import os

DB_PATH = os.path.join(os.path.dirname(__file__), "data", "leads.db")

def setup_high_purchasing_campaigns():
    print("Configurando Campanhas de Alto Poder Aquisitivo (Curitiba e Sul/Sudeste)...")
    conn = sqlite3.connect(DB_PATH)
    cursor = conn.cursor()

    # Foco em cidades com alto PIB per capita e IDH (Curitiba, Balneário, Florianópolis, interior de SP premium)
    campaigns = [
        ("Curitiba — Indústrias High-Tech", "Automação Industrial OR Robótica OR Software ERP", "Curitiba, PR", 200),
        ("Curitiba — Clínicas e Estética", "Cirurgia Plástica OR Dermatologia OR Implante Dentário", "Curitiba, PR", 150),
        ("Balneário Camboriú — Imobiliário Luxo", "Construtora Luxo OR Design de Interiores OR Automação Residencial", "Balneário Camboriú, SC", 150),
        ("Florianópolis — Tech & Inovação", "Startup OR Fintech OR Inteligência Artificial", "Florianópolis, SC", 150),
        ("Alphaville/Barueri — Corporate Services", "Consultoria Tributária OR Outsourcing OR BPO", "Alphaville, Barueri, SP", 150)
    ]

    for name, kw, loc, goal in campaigns:
        cursor.execute("SELECT id FROM campaigns WHERE name = ?", (name,))
        if not cursor.fetchone():
            cursor.execute("""
                INSERT INTO campaigns (name, keyword, location, goal, status, expand_auto, created_at)
                VALUES (?, ?, ?, ?, 'active', 1, datetime('now', 'localtime'))
            """, (name, kw, loc, goal))
            print(f"Campanha '{name}' criada.")
        else:
            print(f"Campanha '{name}' já existe.")

    conn.commit()
    conn.close()

if __name__ == "__main__":
    setup_high_purchasing_campaigns()
